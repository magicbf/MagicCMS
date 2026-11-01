<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Safe software updates.
 *
 * An update package is a zip of the MagicCMS *code* (see tools/build-release.php) with a release.json.
 * It can only ever touch code folders: config/, uploads/, cache/, backups/ (your settings, content and files)
 * and anything the package does not contain (your own themes and plugins) are never written to or deleted.
 *
 * Steps: validate package → pre-flight checks → database backup → back up every file that will be replaced →
 * maintenance mode → atomic file replacement → (next request) migrations → finish. Any failure rolls the code
 * and database back automatically.
 */
final class Updater
{
    public const DIRS = ['assets', 'core', 'docs', 'lang', 'migrations', 'modules', 'panels', 'plugins', 'themes', 'tools', 'views'];
    public const FILES = ['index.php', 'README.md', 'LICENSE', 'nginx.conf.sample', 'release.json'];
    private const EXT = '/\.(php|css|js|json|md|txt|svg|png|jpe?g|gif|webp|ico|woff2?|sql|html|sample|ics)$/i';
    private const MAX_FILES = 6000;
    private const MAX_BYTES = 120 * 1048576;

    public static function manifestFile(): string
    {
        return MCMS_ROOT . '/config/code-manifest.json';
    }

    private static function marker(): string
    {
        return Backup::dir() . '/update-pending.json';
    }

    // ------------------------------------------------------------------ baseline / local changes
    /** @return string[] code files of this installation (relative paths) */
    public static function localFiles(): array
    {
        $out = [];
        foreach (self::DIRS as $d) {
            if (!is_dir(MCMS_ROOT . '/' . $d)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(MCMS_ROOT . '/' . $d, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && !$f->isLink() && preg_match(self::EXT, $f->getFilename()) && !preg_match('#/(fonts|uploads)/#', $f->getPathname())) {
                    $out[] = substr($f->getPathname(), strlen(MCMS_ROOT) + 1);
                }
            }
        }
        foreach (self::FILES as $f) {
            if (is_file(MCMS_ROOT . '/' . $f) && $f !== 'release.json') {
                $out[] = $f;
            }
        }
        sort($out);
        return $out;
    }

    public static function hashAll(): array
    {
        $h = [];
        foreach (self::localFiles() as $f) {
            $h[$f] = hash_file('sha256', MCMS_ROOT . '/' . $f);
        }
        return $h;
    }

    /** Baseline = hashes of the code as installed/updated. Created lazily for installs that predate it. */
    public static function baseline(): array
    {
        $f = self::manifestFile();
        $m = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
        if (!is_array($m) || empty($m['files'])) {
            $m = ['version' => MCMS_VERSION, 'created' => gmdate('c'), 'files' => self::hashAll()];
            @file_put_contents($f, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        return $m;
    }

    /** @return string[] bundled files that differ from the baseline (customised by the site owner) */
    public static function locallyModified(): array
    {
        $base = self::baseline()['files'];
        $mod = [];
        foreach ($base as $f => $h) {
            $p = MCMS_ROOT . '/' . $f;
            if (is_file($p) && hash_file('sha256', $p) !== $h) {
                $mod[] = $f;
            }
        }
        return $mod;
    }

    // ------------------------------------------------------------------ package inspection
    /** Normalise a zip entry path; null = not acceptable. */
    private static function cleanPath(string $n, string $prefix): ?string
    {
        if ($prefix !== '' && str_starts_with($n, $prefix)) {
            $n = substr($n, strlen($prefix));
        }
        if ($n === '' || str_ends_with($n, '/') || str_contains($n, "\0") || str_contains($n, '\\') || str_starts_with($n, '/') || preg_match('#(^|/)\.\.?(/|$)#', $n) || preg_match('#(^|/)\.#', $n)) {
            return null;
        }
        return $n;
    }

    /**
     * Validate a package without touching anything.
     * @return array{ok:bool,errors:string[],warnings:string[],version:string,notes:string,files:array<string,int>,modified:string[],replace:int,new:int,release:array}
     */
    public static function inspect(string $zipFile): array
    {
        $r = ['ok' => false, 'errors' => [], 'warnings' => [], 'version' => '', 'notes' => '', 'files' => [], 'modified' => [], 'replace' => 0, 'new' => 0, 'release' => []];
        if (!class_exists(\ZipArchive::class)) {
            $r['errors'][] = t('The PHP zip extension is required for updates.');
            return $r;
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $r['errors'][] = t('This is not a valid zip file.');
            return $r;
        }
        // optional single top-level folder (GitHub style "MagicCMS-1.2.0/")
        $prefix = '';
        $rel = $zip->locateName('release.json');
        if ($rel === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('#^([^/]+/)release\.json$#', (string) $zip->getNameIndex($i), $m)) {
                    $prefix = $m[1];
                    $rel = $i;
                    break;
                }
            }
        }
        if ($rel === false) {
            $r['errors'][] = t('release.json is missing – this is not a MagicCMS update package.');
            return $r;
        }
        $meta = json_decode((string) $zip->getFromIndex($rel), true);
        if (!is_array($meta) || ($meta['name'] ?? '') !== 'MagicCMS' || !preg_match('/^\d+\.\d+\.\d+([\-+][\w.]+)?$/', (string) ($meta['version'] ?? ''))) {
            $r['errors'][] = t('release.json is invalid.');
            return $r;
        }
        $r['release'] = $meta;
        $r['version'] = (string) $meta['version'];
        $r['notes'] = mb_substr((string) ($meta['notes'] ?? ''), 0, 4000);
        if (version_compare($r['version'], MCMS_VERSION, '<')) {
            $r['errors'][] = t('This package (v{a}) is older than the installed version (v{b}). Downgrades are not supported – use a backup instead.', ['a' => $r['version'], 'b' => MCMS_VERSION]);
        } elseif (version_compare($r['version'], MCMS_VERSION, '==')) {
            $r['warnings'][] = t('Same version as installed – this will re-install the code files (a repair).');
        }
        if (isset($meta['min_php']) && version_compare(PHP_VERSION, (string) $meta['min_php'], '<')) {
            $r['errors'][] = t('This version needs PHP {a} or newer (this server runs {b}).', ['a' => $meta['min_php'], 'b' => PHP_VERSION]);
        }
        foreach ((array) ($meta['requires_ext'] ?? []) as $ext) {
            if (!extension_loaded((string) $ext)) {
                $r['errors'][] = t('The PHP extension “{e}” is required.', ['e' => $ext]);
            }
        }
        if (isset($meta['min_from']) && version_compare(MCMS_VERSION, (string) $meta['min_from'], '<')) {
            $r['errors'][] = t('Update to v{a} first – this package can only be applied on top of v{a} or newer.', ['a' => $meta['min_from']]);
        }
        $hashes = (array) ($meta['files'] ?? []);
        $total = 0;
        $base = self::baseline()['files'];
        $modified = array_flip(self::locallyModified());
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            $raw = (string) $st['name'];
            if ($prefix !== '' && !str_starts_with($raw, $prefix)) {
                continue;
            }
            $n = self::cleanPath($raw, $prefix);
            if ($n === null) {
                if (!str_ends_with($raw, '/')) {
                    $r['errors'][] = t('Unsafe path in package: {p}', ['p' => $raw]);
                }
                continue;
            }
            if ($n === 'release.json' || $n === 'setup.php') {
                continue; // never (re)install the setup wizard
            }
            $top = explode('/', $n)[0];
            if (!(in_array($top, self::DIRS, true) && str_contains($n, '/')) && !in_array($n, self::FILES, true)) {
                $r['errors'][] = t('File outside the allowed code folders: {p}', ['p' => $n]);
                continue;
            }
            if (!preg_match(self::EXT, $n)) {
                $r['errors'][] = t('File type not allowed: {p}', ['p' => $n]);
                continue;
            }
            // symlinks (unix mode 0120000)
            $zip->getExternalAttributesIndex($i, $opsys, $attr);
            if ($opsys === \ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
                $r['errors'][] = t('Symbolic links are not allowed: {p}', ['p' => $n]);
                continue;
            }
            $total += (int) $st['size'];
            if ($st['size'] > 25 * 1048576) {
                $r['errors'][] = t('File too large: {p}', ['p' => $n]);
                continue;
            }
            if (count($r['files']) >= self::MAX_FILES || $total > self::MAX_BYTES) {
                $r['errors'][] = t('The package is unreasonably large.');
                break;
            }
            $data = $zip->getFromIndex($i);
            if ($data === false) {
                $r['errors'][] = t('Cannot read {p}', ['p' => $n]);
                continue;
            }
            if (isset($hashes[$n]) && !hash_equals((string) $hashes[$n], hash('sha256', $data))) {
                $r['errors'][] = t('Checksum mismatch (damaged or tampered file): {p}', ['p' => $n]);
                continue;
            }
            if (str_ends_with($n, '.php')) {
                try {
                    token_get_all($data, TOKEN_PARSE);
                } catch (\ParseError $e) {
                    $r['errors'][] = t('PHP syntax error in {p}: {m}', ['p' => $n, 'm' => $e->getMessage()]);
                    continue;
                }
            }
            $r['files'][$n] = (int) $st['size'];
            if (is_file(MCMS_ROOT . '/' . $n)) {
                $r['replace']++;
                if (isset($modified[$n])) {
                    $r['modified'][] = $n;
                }
            } else {
                $r['new']++;
            }
        }
        $zip->close();
        if (!isset($r['files']['core/Bootstrap.php']) || !isset($r['files']['index.php'])) {
            $r['errors'][] = t('The package does not contain the core files.');
        }
        if ($r['modified']) {
            $r['warnings'][] = t('{n} bundled file(s) were customised on this site. They are replaced, and a copy is kept in the code backup.', ['n' => count($r['modified'])]);
        }
        $r['_prefix'] = $prefix;
        $r['ok'] = !$r['errors'];
        return $r;
    }

    /** Pre-flight: can the web server write everything it must? @return string[] problems */
    public static function preflight(array $insp, string $zipFile): array
    {
        $bad = [];
        $dir = Backup::dir();
        if (!is_writable($dir)) {
            $bad[] = t('The backup folder is not writable: {d}', ['d' => $dir]);
        }
        $need = (int) (filesize($zipFile) * 3 + array_sum($insp['files']));
        $free = @disk_free_space(MCMS_ROOT);
        if ($free !== false && $free < $need) {
            $bad[] = t('Not enough free disk space (need about {n} MB).', ['n' => (int) ceil($need / 1048576)]);
        }
        $dirs = [];
        foreach (array_keys($insp['files']) as $f) {
            $p = MCMS_ROOT . '/' . $f;
            $d = is_file($p) ? $p : dirname($p);
            while (!file_exists($d) && $d !== MCMS_ROOT) {
                $d = dirname($d);
            }
            $dirs[$d] = true;
        }
        foreach (array_keys($dirs) as $d) {
            if (!is_writable($d)) {
                $bad[] = t('Not writable: {p}', ['p' => substr($d, strlen(MCMS_ROOT) + 1) ?: '/']);
            }
        }
        return array_slice(array_unique($bad), 0, 12);
    }

    // ------------------------------------------------------------------ apply
    /**
     * Install a validated package. The new code takes over on the next request (which also runs the migrations).
     * @return array{from:string,to:string,files:int,dbBackup:string,codeBackup:string}
     */
    public static function apply(string $zipFile): array
    {
        @set_time_limit(0);
        $insp = self::inspect($zipFile);
        if (!$insp['ok']) {
            throw new \RuntimeException(implode(' ', $insp['errors']));
        }
        if ($bad = self::preflight($insp, $zipFile)) {
            throw new \RuntimeException(implode(' ', $bad));
        }
        if (is_file(self::marker())) {
            throw new \RuntimeException(t('A previous update has not been finished yet. Finish or roll it back first.'));
        }
        $prefix = (string) $insp['_prefix'];
        self::baseline(); // make sure "locally modified" can be reported later
        $db = Backup::create('pre-update')['name'];
        $stamp = gmdate('Ymd-His');
        $codeBackup = 'code-' . MCMS_VERSION . "-$stamp.zip";
        $cb = new \ZipArchive();
        if ($cb->open(Backup::dir() . '/' . $codeBackup, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(t('Cannot write the code backup.'));
        }
        $cb->addFromString('release.json', json_encode(['name' => 'MagicCMS-code-backup', 'version' => MCMS_VERSION, 'created' => gmdate('c')]));
        $created = [];
        foreach (array_keys($insp['files']) as $f) {
            $p = MCMS_ROOT . '/' . $f;
            if (is_file($p)) {
                $cb->addFile($p, 'files/' . $f);
            } else {
                $created[] = $f;
            }
        }
        $cb->addFromString('created.json', json_encode($created));
        $cb->close();

        $prevMaint = (bool) setting('site.maintenance');
        file_put_contents(self::marker(), json_encode([
            'from' => MCMS_VERSION, 'to' => $insp['version'], 'at' => gmdate('c'), 'dbBackup' => $db, 'codeBackup' => $codeBackup,
            'created' => $created, 'maintenance' => $prevMaint, 'status' => 'pending',
        ]));
        Settings::set('site.maintenance', true);

        $zip = new \ZipArchive();
        $zip->open($zipFile);
        $written = 0;
        try {
            foreach (array_keys($insp['files']) as $f) {
                $data = $zip->getFromName($prefix . $f);
                if ($data === false) {
                    throw new \RuntimeException(t('Cannot read {p}', ['p' => $f]));
                }
                self::writeAtomic(MCMS_ROOT . '/' . $f, $data);
                $written++;
            }
        } catch (\Throwable $e) {
            $zip->close();
            self::rollbackFiles($codeBackup, $created);
            @unlink(self::marker());
            Settings::set('site.maintenance', $prevMaint);
            throw new \RuntimeException(t('Update stopped, nothing was changed: {m}', ['m' => $e->getMessage()]), 0, $e);
        }
        $zip->close();
        // new baseline = exactly what the package contains
        $files = [];
        foreach (array_keys($insp['files']) as $f) {
            $files[$f] = hash_file('sha256', MCMS_ROOT . '/' . $f);
        }
        $old = self::baseline()['files'];
        @file_put_contents(self::manifestFile(), json_encode(['version' => $insp['version'], 'created' => gmdate('c'), 'files' => $files + $old], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        return ['from' => MCMS_VERSION, 'to' => $insp['version'], 'files' => $written, 'dbBackup' => $db, 'codeBackup' => $codeBackup];
    }

    private static function writeAtomic(string $dest, string $data): void
    {
        $dir = dirname($dest);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException(t('Cannot create folder {d}', ['d' => substr($dir, strlen(MCMS_ROOT) + 1)]));
        }
        $tmp = $dest . '.mcms-' . bin2hex(random_bytes(3));
        if (file_put_contents($tmp, $data) === false) {
            @unlink($tmp);
            throw new \RuntimeException(t('Cannot write {p}', ['p' => substr($dest, strlen(MCMS_ROOT) + 1)]));
        }
        @chmod($tmp, 0644);
        if (!@rename($tmp, $dest)) {
            @unlink($tmp);
            throw new \RuntimeException(t('Cannot replace {p}', ['p' => substr($dest, strlen(MCMS_ROOT) + 1)]));
        }
    }

    /** Put the old files back and remove files the update created. */
    public static function rollbackFiles(string $codeBackup, array $created): void
    {
        $p = Backup::dir() . '/' . basename($codeBackup);
        $zip = new \ZipArchive();
        if (is_file($p) && $zip->open($p) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $n = (string) $zip->getNameIndex($i);
                if (str_starts_with($n, 'files/') && !str_contains($n, '..')) {
                    try {
                        self::writeAtomic(MCMS_ROOT . '/' . substr($n, 6), (string) $zip->getFromIndex($i));
                    } catch (\Throwable $e) {
                        error_log('MagicCMS rollback: ' . $e->getMessage());
                    }
                }
            }
            $zip->close();
        }
        foreach ($created as $f) {
            if (is_string($f) && !str_contains($f, '..')) {
                @unlink(MCMS_ROOT . '/' . $f);
            }
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    // ------------------------------------------------------------------ pending update lifecycle
    public static function pending(): ?array
    {
        $f = self::marker();
        return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: null) : null;
    }

    /** Called after the new code ran its migrations successfully. */
    public static function complete(): ?array
    {
        $m = self::pending();
        if (!$m) {
            return null;
        }
        Settings::set('site.maintenance', (bool) ($m['maintenance'] ?? false));
        $hist = (array) setting('update.history', []);
        array_unshift($hist, ['from' => $m['from'], 'to' => MCMS_VERSION, 'at' => gmdate('c'), 'dbBackup' => $m['dbBackup'], 'codeBackup' => $m['codeBackup']]);
        Settings::set('update.history', array_slice($hist, 0, 10));
        @unlink(self::marker());
        return $m;
    }

    /** Undo an update: code files and database go back to the state before it. */
    public static function rollback(array $m): void
    {
        self::rollbackFiles((string) $m['codeBackup'], (array) ($m['created'] ?? []));
        if (!empty($m['dbBackup']) && ($p = Backup::path((string) $m['dbBackup']))) {
            Backup::restoreSql($p, false);
        }
        Settings::set('site.maintenance', (bool) ($m['maintenance'] ?? false));
        @unlink(self::marker());
    }

    /** Used by Bootstrap when migrations of a just-installed update fail. */
    public static function rollbackPending(\Throwable $why): bool
    {
        $m = self::pending();
        if (!$m) {
            return false;
        }
        error_log('MagicCMS update failed, rolling back: ' . $why->getMessage());
        try {
            self::rollback($m);
            return true;
        } catch (\Throwable $e) {
            error_log('MagicCMS rollback failed: ' . $e->getMessage());
            return false;
        }
    }

    // ------------------------------------------------------------------ online check
    public static function feedUrl(): string
    {
        return (string) setting('update.feed', 'https://api.github.com/repos/magicbf/MagicCMS/releases/latest');
    }

    private static function http(string $url, int $timeout = 10, int $max = 5 * 1048576): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if (!preg_match('#^https://#i', $url) && !($local && preg_match('#^http://#i', $url))) {
            throw new \RuntimeException(t('Updates are only fetched over HTTPS.'));
        }
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "User-Agent: MagicCMS/" . MCMS_VERSION . "\r\nAccept: application/json, application/octet-stream\r\n", 'follow_location' => 1, 'max_redirects' => 4]]);
        $fh = @fopen($url, 'rb', false, $ctx);
        if (!$fh) {
            throw new \RuntimeException(t('Could not reach the update server.'));
        }
        $data = stream_get_contents($fh, $max + 1);
        fclose($fh);
        if ($data === false || strlen($data) > $max) {
            throw new \RuntimeException(t('The update server sent an unexpected response.'));
        }
        return $data;
    }

    /** @return array{version:string,notes:string,url:string,sha256:string,newer:bool} */
    public static function latest(): array
    {
        $j = json_decode(self::http(self::feedUrl()), true);
        if (!is_array($j)) {
            throw new \RuntimeException(t('The update server sent an unexpected response.'));
        }
        $version = ltrim((string) ($j['version'] ?? $j['tag_name'] ?? ''), 'vV');
        $url = (string) ($j['url'] ?? '');
        $sha = (string) ($j['sha256'] ?? '');
        foreach ((array) ($j['assets'] ?? []) as $a) { // GitHub release
            $n = (string) ($a['name'] ?? '');
            if (!$url && preg_match('/\.zip$/i', $n)) {
                $url = (string) ($a['browser_download_url'] ?? '');
            }
            if (!$sha && preg_match('/\.sha256$/i', $n)) {
                try {
                    $sha = strtolower(substr(trim(self::http((string) $a['browser_download_url'], 10, 4096)), 0, 64));
                } catch (\Throwable) {
                }
            }
        }
        if (!preg_match('/^\d+\.\d+\.\d+([\-+][\w.]+)?$/', $version)) {
            throw new \RuntimeException(t('The update server did not announce a valid version.'));
        }
        return ['version' => $version, 'notes' => mb_substr((string) ($j['notes'] ?? $j['body'] ?? ''), 0, 4000), 'url' => $url, 'sha256' => preg_match('/^[a-f0-9]{64}$/', $sha) ? $sha : '', 'newer' => version_compare($version, MCMS_VERSION, '>')];
    }

    /** Download a package into the backup folder (never into the web root). @return string path */
    public static function download(string $url, string $sha256): string
    {
        $data = self::http($url, 60, self::MAX_BYTES);
        if ($sha256 !== '' && !hash_equals($sha256, hash('sha256', $data))) {
            throw new \RuntimeException(t('The download does not match its published checksum – aborted.'));
        }
        $p = Backup::dir() . '/.incoming-' . bin2hex(random_bytes(6)) . '.zip';
        file_put_contents($p, $data);
        return $p;
    }
}
