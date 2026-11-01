<?php
declare(strict_types=1);

namespace MagicCMS\Core;

use PDO;

/**
 * Database (and optionally uploads) backups.
 *  - db-<time>-<label>.sql.gz   one SQL statement per line, header comments, "-- end" marker
 *  - full-<time>-<label>.zip    database.sql + uploads/ + manifest.json
 * Files live in backups/ (or config app.backup_dir) which is protected from web access.
 */
final class Backup
{
    private const NAME = '/^(db|full)-\d{8}-\d{6}-[a-z0-9\-]{1,24}\.(sql\.gz|zip)$/';

    public static function dir(): string
    {
        $d = (string) (Bootstrap::$config['app']['backup_dir'] ?? (MCMS_ROOT . '/backups'));
        if (!is_dir($d)) {
            @mkdir($d, 0750, true);
        }
        if (is_dir($d) && !is_file($d . '/.htaccess')) {
            @file_put_contents($d . '/.htaccess', "# Backups contain private data – never serve them\nRequire all denied\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
            @file_put_contents($d . '/index.html', '');
        }
        return $d;
    }

    /** Remove abandoned temporary files (interrupted uploads/downloads). */
    public static function cleanTemp(): void
    {
        foreach (glob(self::dir() . '/.*') ?: [] as $f) {
            if (is_file($f) && preg_match('/^\.(incoming|upload|restore|tmp)-/', basename($f)) && filemtime($f) < time() - 6 * 3600) {
                @unlink($f);
            }
        }
    }

    public static function validName(string $n): bool
    {
        return (bool) preg_match(self::NAME, $n);
    }

    public static function path(string $name): ?string
    {
        $p = self::dir() . '/' . $name;
        return self::validName($name) && is_file($p) ? $p : null;
    }

    /** @return string[] prefixed base tables */
    public static function tables(Database $db): array
    {
        $like = addcslashes($db->prefix(), '_%') . '%';
        return array_column($db->all("SELECT table_name AS t FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' AND table_name LIKE ? ORDER BY table_name", [$like]), 't');
    }

    private static function lit(PDO $pdo, $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        $v = (string) $v;
        if ($v !== '' && !mb_check_encoding($v, 'UTF-8')) {
            return '0x' . bin2hex($v);
        }
        return str_replace(["\r", "\n"], ['\\r', '\\n'], $pdo->quote($v)); // keep every statement on one line
    }

    /** Write a gzip SQL dump. @return array{file:string,size:int,tables:int,rows:int} */
    public static function dump(Database $db, string $file, string $label): array
    {
        @set_time_limit(0);
        $pdo = $db->pdo();
        $gz = gzopen($file, 'wb6');
        if (!$gz) {
            throw new \RuntimeException(t('Cannot write the backup file.'));
        }
        $tables = self::tables($db);
        $w = static fn(string $s) => gzwrite($gz, $s . "\n");
        $w('-- MagicCMS backup');
        $w('-- version: ' . MCMS_VERSION);
        $w('-- schema: ' . Migrator::current($db));
        $w('-- prefix: ' . $db->prefix());
        $w('-- created: ' . gmdate('c'));
        $w('-- label: ' . $label);
        $w('-- tables: ' . count($tables));
        $w("SET NAMES utf8mb4;");
        $w("SET FOREIGN_KEY_CHECKS=0;");
        $w("SET UNIQUE_CHECKS=0;");
        $w("SET sql_mode='NO_AUTO_VALUE_ON_ZERO';");
        $rows = 0;
        $buffered = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
        try {
            foreach ($tables as $t) {
                $create = (string) ($pdo->query('SHOW CREATE TABLE `' . $t . '`')->fetch(PDO::FETCH_NUM)[1] ?? '');
                $w('DROP TABLE IF EXISTS `' . $t . '`;');
                $w(preg_replace('/\s*\R\s*/', ' ', $create) . ';');
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $st = $pdo->query('SELECT * FROM `' . $t . '`', PDO::FETCH_NUM);
                $batch = [];
                $len = 0;
                while (($r = $st->fetch(PDO::FETCH_NUM)) !== false) {
                    $row = '(' . implode(',', array_map(static fn($v) => self::lit($pdo, $v), $r)) . ')';
                    $batch[] = $row;
                    $len += strlen($row);
                    $rows++;
                    if (count($batch) >= 200 || $len > 600000) {
                        $w('INSERT INTO `' . $t . '` VALUES ' . implode(',', $batch) . ';');
                        $batch = [];
                        $len = 0;
                    }
                }
                $st->closeCursor();
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                if ($batch) {
                    $w('INSERT INTO `' . $t . '` VALUES ' . implode(',', $batch) . ';');
                }
            }
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
        }
        $w('SET FOREIGN_KEY_CHECKS=1;');
        $w('-- end');
        gzclose($gz);
        return ['file' => $file, 'size' => (int) filesize($file), 'tables' => count($tables), 'rows' => $rows];
    }

    /** Create a backup. $uploads=true makes a full zip (database + uploads). @return array{name:string,size:int,tables:int,rows:int} */
    public static function create(string $label = 'manual', bool $uploads = false): array
    {
        $label = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), '-') ?: 'manual';
        $label = substr($label, 0, 24);
        $dir = self::dir();
        $t = time();
        while (glob("$dir/*-" . gmdate('Ymd-His', $t) . "-$label.*")) { // never overwrite an existing backup
            $t++;
        }
        $stamp = gmdate('Ymd-His', $t);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \RuntimeException(t('The backup folder is not writable: {d}', ['d' => $dir]));
        }
        $db = Bootstrap::$db;
        if (!$uploads || !class_exists(\ZipArchive::class)) {
            $name = "db-$stamp-$label.sql.gz";
            $r = self::dump($db, "$dir/$name", $label);
            self::prune();
            return ['name' => $name, 'size' => $r['size'], 'tables' => $r['tables'], 'rows' => $r['rows']];
        }
        $tmp = "$dir/.tmp-$stamp.sql.gz";
        $r = self::dump($db, $tmp, $label);
        $name = "full-$stamp-$label.zip";
        $zip = new \ZipArchive();
        if ($zip->open("$dir/$name", \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException(t('Cannot write the backup file.'));
        }
        $zip->addFile($tmp, 'database.sql.gz');
        $zip->addFromString('manifest.json', json_encode(['app' => 'MagicCMS', 'version' => MCMS_VERSION, 'schema' => Migrator::current($db), 'prefix' => $db->prefix(), 'created' => gmdate('c'), 'tables' => $r['tables'], 'rows' => $r['rows']], JSON_PRETTY_PRINT));
        $root = MCMS_ROOT . '/uploads';
        if (is_dir($root)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && !$f->isLink() && !str_starts_with($f->getFilename(), '.')) {
                    $zip->addFile($f->getPathname(), 'uploads/' . substr($f->getPathname(), strlen($root) + 1));
                }
            }
        }
        $zip->close();
        @unlink($tmp);
        self::prune();
        return ['name' => $name, 'size' => (int) filesize("$dir/$name"), 'tables' => $r['tables'], 'rows' => $r['rows']];
    }

    /** @return array<int,array{name:string,size:int,time:int,type:string,label:string}> newest first */
    public static function all(): array
    {
        self::cleanTemp();
        $out = [];
        foreach (glob(self::dir() . '/*') ?: [] as $f) {
            $n = basename($f);
            if (self::validName($n)) {
                preg_match('/^(db|full)-(\d{8})-(\d{6})-(.+?)\./', $n, $m);
                $out[] = ['name' => $n, 'size' => (int) filesize($f), 'time' => (int) strtotime($m[2] . 'T' . $m[3] . 'Z'), 'type' => $m[1], 'label' => $m[4]];
            }
        }
        usort($out, static fn($a, $b) => $b['time'] <=> $a['time'] ?: strcmp($b['name'], $a['name']));
        return $out;
    }

    public static function delete(string $name): bool
    {
        $p = self::path($name);
        return $p !== null && @unlink($p);
    }

    /** Keep the newest N routine backups and the newest 5 safety backups (pre-update / pre-restore). */
    public static function prune(): void
    {
        $keep = max(1, min(100, (int) setting('backup.keep', 10)));
        $routine = $safety = 0;
        foreach (self::all() as $b) {
            if (str_starts_with($b['label'], 'pre-')) {
                if (++$safety > 5) {
                    self::delete($b['name']);
                }
            } elseif (++$routine > $keep) {
                self::delete($b['name']);
            }
        }
    }

    // ------------------------------------------------------------------ restore
    /** Open a .sql.gz and read its header. @return array{version:string,schema:int,prefix:string,created:string}|null */
    public static function header(string $gzFile): ?array
    {
        $gz = @gzopen($gzFile, 'rb');
        if (!$gz) {
            return null;
        }
        $h = [];
        for ($i = 0; $i < 12 && ($line = gzgets($gz, 4096)) !== false; $i++) {
            if (preg_match('/^-- (\w+): (.*)$/', rtrim($line), $m)) {
                $h[$m[1]] = $m[2];
            }
            if ($i === 0 && !str_starts_with($line, '-- MagicCMS backup')) {
                gzclose($gz);
                return null;
            }
        }
        gzclose($gz);
        return isset($h['prefix'], $h['schema']) ? ['version' => (string) ($h['version'] ?? '?'), 'schema' => (int) $h['schema'], 'prefix' => $h['prefix'], 'created' => (string) ($h['created'] ?? '')] : null;
    }

    /** True when $sql is exactly ONE statement: the only ';' outside quotes/backticks is the final character. */
    public static function singleStatement(string $sql): bool
    {
        $n = strlen($sql);
        $q = '';
        for ($i = 0; $i < $n; $i++) {
            $c = $sql[$i];
            if ($q !== '') {
                if ($c === '\\' && $q !== '`') {
                    $i++;
                } elseif ($c === $q) {
                    $q = '';
                }
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') {
                $q = $c;
            } elseif ($c === ';') {
                return $i === $n - 1;
            } elseif ($c === '#' || ($c === '-' && ($sql[$i + 1] ?? '') === '-') || ($c === '/' && ($sql[$i + 1] ?? '') === '*')) {
                return false; // comments could hide a second statement
            }
        }
        return $q === '' && str_ends_with($sql, ';');
    }

    /** Only the statements our own dumps contain are allowed – a backup file can never run anything else. */
    public static function allowedLine(string $line, string $prefix): bool
    {
        $t = preg_quote($prefix, '/');
        $ok = preg_match('/^SET (NAMES utf8mb4|FOREIGN_KEY_CHECKS=[01]|UNIQUE_CHECKS=[01]|sql_mode=\'NO_AUTO_VALUE_ON_ZERO\');$/', $line)
            || preg_match('/^DROP TABLE IF EXISTS `' . $t . '[a-z0-9_]+`;$/', $line)
            || preg_match('/^CREATE TABLE `' . $t . '[a-z0-9_]+` \(/', $line)
            || preg_match('/^INSERT INTO `' . $t . '[a-z0-9_]+` VALUES \(/', $line);
        return $ok && self::singleStatement($line) && !preg_match('/^CREATE TABLE .*\b(SELECT|DATA DIRECTORY|INDEX DIRECTORY)\b/is', preg_replace("/'(?:[^'\\\\]|\\\\.)*'/s", "''", $line) ?? $line);
    }

    /** Verify a dump is complete and belongs to this installation's table prefix. @return string|null error */
    public static function verify(string $gzFile): ?string
    {
        $h = self::header($gzFile);
        if (!$h) {
            return t('This is not a MagicCMS database backup.');
        }
        if ($h['prefix'] !== Bootstrap::$db->prefix()) {
            return t('The backup uses the table prefix “{a}”, this site uses “{b}”.', ['a' => $h['prefix'], 'b' => Bootstrap::$db->prefix()]);
        }
        if ($h['schema'] > Migrator::latest()) {
            return t('The backup comes from a newer MagicCMS (schema {a}); update the software first.', ['a' => $h['schema']]);
        }
        $gz = gzopen($gzFile, 'rb');
        $last = '';
        $buf = '';
        $prefix = Bootstrap::$db->prefix();
        while (($chunk = gzgets($gz, 1 << 20)) !== false) {
            $buf .= $chunk;
            if (!str_ends_with($buf, "\n")) {
                continue; // long line spanning several reads
            }
            $line = trim($buf);
            $buf = '';
            if ($line === '') {
                continue;
            }
            $last = $line;
            if (!str_starts_with($line, '--') && !self::allowedLine($line, $prefix)) {
                gzclose($gz);
                return t('The backup contains statements that MagicCMS never writes – refused for safety.');
            }
        }
        gzclose($gz);
        return $last === '-- end' ? null : t('The backup file is incomplete or damaged.');
    }

    /**
     * Replace the whole database with a backup. A safety backup is taken first and restored automatically if something fails.
     * @return array{statements:int,safety:string}
     */
    public static function restoreSql(string $gzFile, bool $safety = true): array
    {
        @set_time_limit(0);
        if ($err = self::verify($gzFile)) {
            throw new \RuntimeException($err);
        }
        $db = Bootstrap::$db;
        $pdo = $db->pdo();
        $pre = '';
        if ($safety) {
            $pre = self::create('pre-restore')['name'];
        }
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            foreach (self::tables($db) as $t) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $t . '`');
            }
            $gz = gzopen($gzFile, 'rb');
            $n = 0;
            $buf = '';
            while (($chunk = gzgets($gz, 1 << 20)) !== false) {
                $buf .= $chunk;
                if (!str_ends_with(rtrim($buf, "\r"), "\n")) {
                    continue; // long line spanning several reads
                }
                $line = trim($buf);
                $buf = '';
                if ($line === '' || str_starts_with($line, '--')) {
                    continue;
                }
                $pdo->exec($line);
                $n++;
            }
            gzclose($gz);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            Settings::load();
            return ['statements' => $n, 'safety' => $pre];
        } catch (\Throwable $e) {
            if ($pre !== '' && ($p = self::path($pre))) {
                try {
                    self::restoreSql($p, false); // roll back to the state before we started
                } catch (\Throwable) {
                }
            }
            throw new \RuntimeException(t('Restore failed and the previous state was put back: {m}', ['m' => $e->getMessage()]), 0, $e);
        }
    }

    /** Restore from a stored backup (db or full). Full backups also merge their uploads/ back in. */
    public static function restore(string $name): array
    {
        $p = self::path($name);
        if (!$p) {
            throw new \RuntimeException(t('Backup not found.'));
        }
        if (str_ends_with($name, '.sql.gz')) {
            return self::restoreSql($p) + ['files' => 0];
        }
        $zip = new \ZipArchive();
        if ($zip->open($p) !== true || $zip->locateName('database.sql.gz') === false) {
            throw new \RuntimeException(t('This is not a MagicCMS backup archive.'));
        }
        $tmp = self::dir() . '/.restore-' . bin2hex(random_bytes(4)) . '.sql.gz';
        file_put_contents($tmp, $zip->getFromName('database.sql.gz'));
        try {
            $r = self::restoreSql($tmp);
        } finally {
            @unlink($tmp);
        }
        $files = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = (string) $zip->getNameIndex($i);
            if (!str_starts_with($n, 'uploads/') || str_ends_with($n, '/') || str_contains($n, '..') || str_contains($n, '\\') || preg_match('#/\.#', $n)) {
                continue;
            }
            if (preg_match('/\.(php|phtml|phar|php\d|pl|py|cgi|sh)$/i', $n)) {
                continue;
            }
            $dest = MCMS_ROOT . '/' . $n;
            if (!is_dir(dirname($dest))) {
                @mkdir(dirname($dest), 0755, true);
            }
            if (file_put_contents($dest, $zip->getFromIndex($i)) !== false) {
                $files++;
            }
        }
        $zip->close();
        return $r + ['files' => $files];
    }

    // ------------------------------------------------------------------ automatic backups
    public static function autoDue(): bool
    {
        $days = (int) setting('backup.auto_days', 7);
        if ($days <= 0) {
            return false;
        }
        $last = (int) setting('backup.last_auto', 0);
        return time() - $last >= $days * 86400;
    }

    public static function autoRun(): void
    {
        Settings::set('backup.last_auto', time()); // set first so concurrent requests don't pile up
        try {
            self::create('auto');
        } catch (\Throwable $e) {
            error_log('MagicCMS auto backup failed: ' . $e->getMessage());
        }
    }
}
