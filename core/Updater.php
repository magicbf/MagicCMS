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
        $r = ['ok' => false, 'errors' => [], 'warnings' => [], 'version' => '', 'notes' => '', 'files' => [], 'modified' => [], 'replace' => 0, 'new' => 0, 'release' => [],
            'hash' => [], 'todo' => [], 'added' => [], 'changed' => [], 'unchanged' => 0, 'obsolete' => [], 'kept' => [], 'commit' => '', 'phpChecked' => 0];
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
        } elseif (version_compare($r['version'], MCMS_VERSION, '==') && empty($meta['commit'])) {
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
            $h = hash('sha256', $data);
            $r['hash'][$n] = $h;
            if (str_ends_with($n, '.php')) {
                $r['phpChecked']++;
            }
            $local = MCMS_ROOT . '/' . $n;
            if (!is_file($local)) {
                $r['new']++;
                $r['added'][] = $n;
                $r['todo'][] = $n;
            } elseif (!hash_equals($h, (string) hash_file('sha256', $local))) {
                $r['replace']++;
                $r['changed'][] = $n;
                $r['todo'][] = $n;
                if (isset($modified[$n])) {
                    $r['modified'][] = $n;   // customised on this site AND different from the package → will be overwritten
                }
            } else {
                $r['unchanged']++;           // identical → left alone
            }
        }
        $zip->close();
        $r['commit'] = preg_match('/^[a-f0-9]{40}$/', (string) ($meta['commit'] ?? '')) ? (string) $meta['commit'] : '';
        // files the PREVIOUS release shipped and this one no longer contains: removed after the update has proven itself
        $shipped = self::baseline()['shipped'] ?? null;
        if (is_array($shipped)) {
            foreach ($shipped as $f) {
                if (!is_string($f) || isset($r['files'][$f]) || !self::purgeable($f) || !is_file(MCMS_ROOT . '/' . $f)) {
                    continue;
                }
                if (isset($base[$f]) && hash_equals((string) $base[$f], (string) hash_file('sha256', MCMS_ROOT . '/' . $f))) {
                    $r['obsolete'][] = $f;
                } else {
                    $r['kept'][] = $f;   // customised on this site: never deleted
                }
            }
        }
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

    /** Only files from the plain code folders may ever be removed by an update; never migrations/ or lang/ (site-specific additions). */
    public static function purgeable(string $f): bool
    {
        if ($f === '' || str_contains($f, '..') || str_starts_with($f, '/') || str_contains($f, "\0") || !str_contains($f, '/') || !preg_match(self::EXT, $f)) {
            return false;
        }
        $top = explode('/', $f)[0];
        return in_array($top, self::DIRS, true) && !in_array($top, ['migrations', 'lang'], true);
    }

    private static function pruneEmptyDirs(string $dir): void
    {
        $root = MCMS_ROOT . '/';
        while (str_starts_with($dir . '/', $root) && $dir !== MCMS_ROOT && is_dir($dir) && !(new \FilesystemIterator($dir))->valid()) {
            if (!@rmdir($dir)) {
                break;
            }
            $dir = dirname($dir);
        }
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
        foreach (($insp['todo'] ?? array_keys($insp['files'])) as $f) {
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
        if (!$insp['todo'] && !$insp['obsolete']) {
            throw new \RuntimeException(t('Nothing to update: every file already matches this version.'));
        }
        $prefix = (string) $insp['_prefix'];
        self::baseline(); // make sure "locally modified" can be reported later
        $db = Backup::create('pre-update')['name'];
        $t = time();
        while (is_file(Backup::dir() . '/code-' . MCMS_VERSION . '-' . gmdate('Ymd-His', $t) . '.zip')) {
            $t++;
        }
        $stamp = gmdate('Ymd-His', $t);
        $codeBackup = 'code-' . MCMS_VERSION . "-$stamp.zip";
        $cb = new \ZipArchive();
        if ($cb->open(Backup::dir() . '/' . $codeBackup, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException(t('Cannot write the code backup.'));
        }
        $cb->addFromString('release.json', json_encode(['name' => 'MagicCMS-code-backup', 'version' => MCMS_VERSION, 'created' => gmdate('c')]));
        $created = [];
        foreach (array_merge($insp['todo'], $insp['obsolete']) as $f) {   // everything this update replaces or later removes can be restored
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
            'obsolete' => $insp['obsolete'], 'commit' => $insp['commit'], 'prevCommit' => self::installedCommit(), 'count' => count($insp['todo']),
        ]));
        Settings::set('site.maintenance', true);

        $zip = new \ZipArchive();
        $zip->open($zipFile);
        $written = 0;
        try {
            foreach ($insp['todo'] as $f) {
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
        @file_put_contents(self::manifestFile(), json_encode(['version' => $insp['version'], 'created' => gmdate('c'), 'commit' => $insp['commit'], 'shipped' => array_keys($insp['files']), 'files' => $files + $old], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        return ['from' => MCMS_VERSION, 'to' => $insp['version'], 'files' => $written, 'dbBackup' => $db, 'codeBackup' => $codeBackup, 'changed' => count($insp['changed']), 'added' => count($insp['added']), 'obsolete' => count($insp['obsolete']), 'unchanged' => $insp['unchanged'], 'commit' => $insp['commit']];
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
        // The new version booted and migrated: only now are files the new release no longer ships removed.
        $purged = [];
        foreach ((array) ($m['obsolete'] ?? []) as $f) {
            if (is_string($f) && self::purgeable($f) && is_file(MCMS_ROOT . '/' . $f) && @unlink(MCMS_ROOT . '/' . $f)) {
                $purged[] = $f;
                self::pruneEmptyDirs(dirname(MCMS_ROOT . '/' . $f));
            }
        }
        if ($purged) {
            $mf = self::manifestFile();
            $man = is_file($mf) ? json_decode((string) file_get_contents($mf), true) : null;
            if (is_array($man)) {
                $man['files'] = array_diff_key((array) ($man['files'] ?? []), array_flip($purged));
                @file_put_contents($mf, json_encode($man, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
        if (preg_match('/^[a-f0-9]{40}$/', (string) ($m['commit'] ?? ''))) {
            Settings::set('update.commit', $m['commit']);
        }
        Settings::set('update.status', ['at' => gmdate('c'), 'newer' => false]);
        $hist = (array) setting('update.history', []);
        array_unshift($hist, ['from' => $m['from'], 'to' => MCMS_VERSION, 'at' => gmdate('c'), 'dbBackup' => $m['dbBackup'], 'codeBackup' => $m['codeBackup'], 'created' => $m['created'] ?? [],
            'commit' => (string) ($m['commit'] ?? ''), 'prevCommit' => (string) ($m['prevCommit'] ?? ''), 'purged' => count($purged), 'count' => (int) ($m['count'] ?? 0)]);
        Settings::set('update.history', array_slice($hist, 0, 10));
        $m['purged'] = $purged;
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

    // ------------------------------------------------------------------ online update (GitHub)
    /** Hosts an update may be fetched from. Anything else (also after a redirect) is refused. */
    private const HOSTS = ['api.github.com', 'codeload.github.com', 'github.com', 'objects.githubusercontent.com', 'release-assets.githubusercontent.com', 'raw.githubusercontent.com'];
    private const REPO_DEFAULT = 'magicbf/MagicCMS';
    private const FEED_DEFAULT = 'https://api.github.com/repos/magicbf/MagicCMS/releases/latest';

    /** "owner/name" of the GitHub repository updates are pulled from. */
    public static function repo(): string
    {
        $r = (string) setting('update.repo', self::REPO_DEFAULT);
        return preg_match('#^[A-Za-z0-9_.\-]{1,39}/[A-Za-z0-9_.\-]{1,100}$#', $r) ? $r : self::REPO_DEFAULT;
    }

    /** 'auto' (latest release, else the default branch) | 'release' | 'branch:<name>' */
    public static function channel(): string
    {
        $c = (string) setting('update.channel', 'auto');
        if ($c === 'auto' || $c === 'release') {
            return $c;
        }
        return preg_match('#^branch:[A-Za-z0-9._/\-]{1,100}$#', $c) && !str_contains($c, '..') ? $c : 'auto';
    }

    public static function feedUrl(): string
    {
        return (string) setting('update.feed', self::FEED_DEFAULT);
    }

    private static function isLoopback(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }

    /** Test hook: base URLs can be pointed at a local server, but ONLY a loopback one. */
    private static function base(string $key, string $default): string
    {
        $v = rtrim((string) setting($key, $default), '/');
        return $v !== $default && self::isLoopback((string) parse_url($v, PHP_URL_HOST)) ? $v : $default;
    }

    private static function assertUrl(string $url): void
    {
        $p = parse_url($url);
        $host = strtolower((string) ($p['host'] ?? ''));
        $scheme = strtolower((string) ($p['scheme'] ?? ''));
        $ok = ($scheme === 'https' && in_array($host, self::HOSTS, true)) || (in_array($scheme, ['http', 'https'], true) && self::isLoopback($host));
        if (!$ok || isset($p['user']) || isset($p['pass'])) {
            throw new \RuntimeException(t('Refused to fetch from “{h}”: updates may only come from GitHub over HTTPS.', ['h' => $host ?: $url]));
        }
    }

    /**
     * One HTTP GET with a size cap, no automatic redirects (each hop is checked against the host list) and TLS verification.
     * @return array{0:int,1:string,2:string} status, Location header, body
     */
    private static function request(string $url, int $max, int $timeout, string $accept): array
    {
        $ua = 'MagicCMS-Updater/' . MCMS_VERSION;
        if (function_exists('curl_init')) {
            $body = '';
            $loc = '';
            $ch = curl_init($url);
            $loopback = self::isLoopback((string) parse_url($url, PHP_URL_HOST));
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                CURLOPT_USERAGENT => $ua, CURLOPT_HTTPHEADER => ['Accept: ' . $accept, 'X-GitHub-Api-Version: 2022-11-28'],
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => $loopback ? (CURLPROTO_HTTPS | CURLPROTO_HTTP) : CURLPROTO_HTTPS,
                CURLOPT_HEADERFUNCTION => static function ($c, $h) use (&$loc) {
                    if (stripos($h, 'location:') === 0) {
                        $loc = trim(substr($h, 9));
                    }
                    return strlen($h);
                },
                CURLOPT_WRITEFUNCTION => static function ($c, $d) use (&$body, $max) {
                    $body .= $d;
                    return strlen($body) > $max ? 0 : strlen($d);   // returning less than received aborts the transfer
                },
            ]);
            $ok = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_errno($ch);
            curl_close($ch);
            if ($ok === false && $err === CURLE_WRITE_ERROR) {
                throw new \RuntimeException(t('The download is larger than the allowed {n} MB – aborted.', ['n' => (int) ($max / 1048576)]));
            }
            if ($ok === false || $code === 0) {
                throw new \RuntimeException(t('Could not reach the update server.'));
            }
            return [$code, $loc, $body];
        }
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'header' => "User-Agent: $ua\r\nAccept: $accept\r\n", 'follow_location' => 0, 'ignore_errors' => true], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fh = @fopen($url, 'rb', false, $ctx);
        if (!$fh) {
            throw new \RuntimeException(t('Could not reach the update server.'));
        }
        $body = stream_get_contents($fh, $max + 1);
        fclose($fh);
        $code = 0;
        $loc = '';
        foreach ((array) ($http_response_header ?? []) as $h) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) {
                $code = (int) $m[1];
            } elseif (stripos($h, 'location:') === 0) {
                $loc = trim(substr($h, 9));
            }
        }
        if ($body === false || strlen($body) > $max) {
            throw new \RuntimeException(t('The download is larger than the allowed {n} MB – aborted.', ['n' => (int) ($max / 1048576)]));
        }
        return [$code, $loc, $body];
    }

    /** GET with manual, host-checked redirects (max 5). */
    private static function fetch(string $url, int $max = 5 * 1048576, int $timeout = 15, string $accept = 'application/vnd.github+json'): string
    {
        for ($i = 0; $i < 6; $i++) {
            self::assertUrl($url);
            [$code, $loc, $body] = self::request($url, $max, $timeout, $accept);
            if (in_array($code, [301, 302, 303, 307, 308], true) && $loc !== '') {
                $pu = parse_url($url);
                $url = preg_match('#^https?://#i', $loc) ? $loc : ($pu['scheme'] . '://' . $pu['host'] . (isset($pu['port']) ? ':' . $pu['port'] : '') . '/' . ltrim($loc, '/'));
                continue;
            }
            if ($code === 403 || $code === 429) {
                throw new \RuntimeException(t('GitHub is limiting requests right now (rate limit). Please try again in a few minutes.'));
            }
            if ($code === 404) {
                throw new \RuntimeException(t('Not found on GitHub (404): is the repository “{r}” public and spelled correctly?', ['r' => self::repo()]), 404);
            }
            if ($code < 200 || $code >= 300) {
                throw new \RuntimeException(t('The update server answered with an error ({c}).', ['c' => $code]), $code);
            }
            return $body;
        }
        throw new \RuntimeException(t('Too many redirects – aborted.'));
    }

    private static function api(string $path, int $timeout = 12): array
    {
        $j = json_decode(self::fetch(self::base('update.api_base', 'https://api.github.com') . $path, 2 * 1048576, $timeout), true);
        if (!is_array($j)) {
            throw new \RuntimeException(t('The update server sent an unexpected response.'));
        }
        return $j;
    }

    /** Installed build (commit sha) – '' when this site was not installed from a tracked commit yet. */
    public static function installedCommit(): string
    {
        $c = (string) setting('update.commit', '');
        return preg_match('/^[a-f0-9]{40}$/', $c) ? $c : '';
    }

    /**
     * What is the newest version on GitHub? Releases first (when published), otherwise the tip of a branch.
     * @return array<string,mixed>
     */
    public static function latest(int $timeout = 12): array
    {
        $feed = self::feedUrl();
        if ($feed !== self::FEED_DEFAULT && $feed !== '') {
            return self::latestFromFeed($feed);   // a custom JSON feed set by the site owner
        }
        $repo = self::repo();
        $ch = self::channel();
        $info = self::api('/repos/' . $repo, $timeout);
        $ref = (string) ($info['default_branch'] ?? 'main');
        $release = null;
        if ($ch === 'auto' || $ch === 'release') {
            try {
                $release = self::api('/repos/' . $repo . '/releases/latest', $timeout);
            } catch (\Throwable $e) {
                if ($ch === 'release') {
                    throw new \RuntimeException(t('This repository has no published release yet. Choose “latest commit of a branch” in the update settings.'), 0, $e);
                }
            }
        } else {
            $ref = substr($ch, 7);
        }
        $kind = $release ? 'release' : 'branch';
        $version = MCMS_VERSION;
        $url = '';
        $sha256 = '';
        $notes = '';
        $asset = false;
        if ($release) {
            $ref = (string) ($release['tag_name'] ?? '');
            $version = ltrim($ref, 'vV');
            $notes = mb_substr((string) ($release['body'] ?? ''), 0, 4000);
            foreach ((array) ($release['assets'] ?? []) as $a) {
                if (!$url && preg_match('/\.zip$/i', (string) ($a['name'] ?? ''))) {
                    $u = (string) ($a['browser_download_url'] ?? '');
                    if (preg_match('#^https://github\.com/#', $u)) {
                        $url = $u;
                        $asset = true;
                        $sha256 = preg_match('/^sha256:([a-f0-9]{64})$/i', (string) ($a['digest'] ?? ''), $m) ? strtolower($m[1]) : '';
                    }
                }
            }
            if (!preg_match('/^\d+\.\d+\.\d+([\-+][\w.]+)?$/', $version)) {
                throw new \RuntimeException(t('The latest release tag “{t}” is not a version number like 1.2.3.', ['t' => $ref]));
            }
        }
        $c = self::api('/repos/' . $repo . '/commits/' . rawurlencode($ref), $timeout);
        $sha = strtolower((string) ($c['sha'] ?? ''));
        if (!preg_match('/^[a-f0-9]{40}$/', $sha)) {
            throw new \RuntimeException(t('The update server sent an unexpected response.'));
        }
        $installed = self::installedCommit();
        $commits = [];
        try {
            if ($installed !== '' && $installed !== $sha) {
                $cmp = self::api('/repos/' . $repo . '/compare/' . $installed . '...' . $sha, $timeout);
                $list = (array) ($cmp['commits'] ?? []);
            } else {
                $list = (array) self::api('/repos/' . $repo . '/commits?sha=' . rawurlencode($ref) . '&per_page=10', $timeout);
            }
            foreach (array_slice(array_reverse($list), 0, 40) as $x) {
                $commits[] = ['sha' => substr((string) ($x['sha'] ?? ''), 0, 7), 'msg' => mb_substr(strtok((string) ($x['commit']['message'] ?? ''), "\n") ?: '', 0, 140), 'author' => mb_substr((string) ($x['commit']['author']['name'] ?? ''), 0, 60), 'date' => (string) ($x['commit']['author']['date'] ?? '')];
            }
            if ($installed !== '' && $installed !== $sha) {
                $commits = array_reverse($commits);   // newest first
            }
        } catch (\Throwable) {
            $commits = [];
        }
        $newer = $kind === 'release'
            ? (version_compare($version, MCMS_VERSION, '>') || (version_compare($version, MCMS_VERSION, '==') && $installed !== '' && $installed !== $sha))
            : ($installed !== $sha);
        return [
            'kind' => $kind, 'repo' => $repo, 'ref' => $ref, 'commit' => $sha, 'short' => substr($sha, 0, 7), 'version' => $version,
            'notes' => $notes !== '' ? $notes : mb_substr((string) ($c['commit']['message'] ?? ''), 0, 1000), 'url' => $url, 'asset' => $asset, 'sha256' => $sha256,
            'newer' => $newer, 'unknown' => $installed === '', 'installed' => $installed, 'commits' => $commits,
            'date' => (string) ($c['commit']['author']['date'] ?? ''), 'author' => (string) ($c['commit']['author']['name'] ?? ''), 'checked' => gmdate('c'),
        ];
    }

    /** Legacy: a plain JSON feed ({version, url, sha256, notes}). */
    private static function latestFromFeed(string $feed): array
    {
        $j = json_decode(self::fetch($feed, 5 * 1048576, 12), true);
        if (!is_array($j)) {
            throw new \RuntimeException(t('The update server sent an unexpected response.'));
        }
        $version = ltrim((string) ($j['version'] ?? $j['tag_name'] ?? ''), 'vV');
        $url = (string) ($j['url'] ?? '');
        $sha = (string) ($j['sha256'] ?? '');
        foreach ((array) ($j['assets'] ?? []) as $a) {
            $n = (string) ($a['name'] ?? '');
            if (!$url && preg_match('/\.zip$/i', $n)) {
                $url = (string) ($a['browser_download_url'] ?? '');
            }
        }
        if (!preg_match('/^\d+\.\d+\.\d+([\-+][\w.]+)?$/', $version)) {
            throw new \RuntimeException(t('The update server did not announce a valid version.'));
        }
        return ['kind' => 'feed', 'repo' => $feed, 'ref' => $version, 'commit' => '', 'short' => '', 'version' => $version, 'notes' => mb_substr((string) ($j['notes'] ?? $j['body'] ?? ''), 0, 4000),
            'url' => $url, 'asset' => true, 'sha256' => preg_match('/^[a-f0-9]{64}$/', $sha) ? $sha : '', 'newer' => version_compare($version, MCMS_VERSION, '>'), 'unknown' => false, 'installed' => '', 'commits' => [], 'date' => '', 'author' => '', 'checked' => gmdate('c')];
    }

    /** Branch names offered in the update settings. @return string[] */
    public static function branches(): array
    {
        $out = [];
        foreach (self::api('/repos/' . self::repo() . '/branches?per_page=30') as $b) {
            if (preg_match('#^[A-Za-z0-9._/\-]{1,100}$#', (string) ($b['name'] ?? ''))) {
                $out[] = (string) $b['name'];
            }
        }
        return $out;
    }

    /** Cached result of the last check (for the dashboard hint). */
    public static function status(): array
    {
        $s = (array) setting('update.status', []);
        return $s;
    }

    /** Check at most every 12 h (superadmin dashboard). Never throws; a slow GitHub must not slow the page down. */
    public static function autoCheck(): void
    {
        if (!setting('update.auto_check', true)) {
            return;
        }
        $s = self::status();
        if (!empty($s['at']) && time() - (int) strtotime((string) $s['at']) < 43200) {
            return;
        }
        try {
            $l = self::latest(4);
            self::remember($l);
        } catch (\Throwable) {
            Settings::set('update.status', ['at' => gmdate('c'), 'newer' => false, 'error' => true]);
        }
    }

    public static function remember(array $l): void
    {
        Settings::set('update.status', ['at' => gmdate('c'), 'newer' => (bool) $l['newer'], 'short' => (string) $l['short'], 'version' => (string) $l['version'], 'ref' => (string) $l['ref'], 'kind' => (string) $l['kind']]);
    }

    /**
     * Download into the backup folder (never into the web root) and return the path of a standard update package.
     * A pre-built release zip is used as is; a source snapshot is converted into the same package format first.
     * @param array<string,mixed> $l result of latest()
     */
    public static function download(array $l, string $legacySha = ''): string
    {
        if (!is_array($l)) {
            throw new \RuntimeException(t('Check for updates first.'));
        }
        if ($l['asset'] ?? false) {
            $data = self::fetch((string) $l['url'], self::MAX_BYTES, 90, 'application/octet-stream');
            if (($l['sha256'] ?? '') !== '' && !hash_equals((string) $l['sha256'], hash('sha256', $data))) {
                throw new \RuntimeException(t('The download does not match its published checksum – aborted.'));
            }
            return self::stage($data);
        }
        $sha = (string) ($l['commit'] ?? '');
        if (!preg_match('/^[a-f0-9]{40}$/', $sha) || !preg_match('#^[A-Za-z0-9_.\-]{1,39}/[A-Za-z0-9_.\-]{1,100}$#', (string) $l['repo'])) {
            throw new \RuntimeException(t('The update server did not announce a usable version.'));
        }
        // pinned to the exact commit that was checked: a branch that moves in the meantime cannot change what we download
        $raw = self::fetch(self::base('update.codeload_base', 'https://codeload.github.com') . '/' . $l['repo'] . '/zip/' . $sha, self::MAX_BYTES, 120, 'application/zip');
        return self::buildPackage($raw, $l);
    }

    /** Delete downloaded packages that were never installed (the admin closed the browser, the session ended …). */
    public static function cleanStaging(int $maxAge = 3600): int
    {
        $n = 0;
        foreach (array_merge(glob(Backup::dir() . '/.incoming-*.zip') ?: [], glob(Backup::dir() . '/.src-*.zip') ?: []) as $f) {
            if (is_file($f) && time() - (int) filemtime($f) > $maxAge && @unlink($f)) {
                $n++;
            }
        }
        return $n;
    }

    private static function stage(string $data): string
    {
        $p = Backup::dir() . '/.incoming-' . bin2hex(random_bytes(6)) . '.zip';
        if (file_put_contents($p, $data) === false) {
            throw new \RuntimeException(t('Cannot write to the backup folder.'));
        }
        return $p;
    }

    /** Turn a GitHub source snapshot into a standard package: only code folders, with a release.json carrying per-file hashes. */
    private static function buildPackage(string $raw, array $l): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException(t('The PHP zip extension is required for updates.'));
        }
        $tmpSrc = Backup::dir() . '/.src-' . bin2hex(random_bytes(6)) . '.zip';
        file_put_contents($tmpSrc, $raw);
        $zip = new \ZipArchive();
        if ($zip->open($tmpSrc) !== true) {
            @unlink($tmpSrc);
            throw new \RuntimeException(t('This is not a valid zip file.'));
        }
        $prefix = '';
        if ($zip->numFiles > 0 && preg_match('#^([^/]+/)#', (string) $zip->getNameIndex(0), $m)) {
            $prefix = $m[1];
        }
        $outPath = Backup::dir() . '/.incoming-' . bin2hex(random_bytes(6)) . '.zip';
        $out = new \ZipArchive();
        $out->open($outPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $hashes = [];
        $skipped = 0;
        $total = 0;
        $version = null;
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $st = $zip->statIndex($i);
                $raw = (string) $st['name'];
                if ($prefix !== '' && !str_starts_with($raw, $prefix)) {
                    throw new \RuntimeException(t('Unsafe path in package: {p}', ['p' => $raw]));
                }
                $n = self::cleanPath($raw, $prefix);
                if ($n === null) {
                    if (preg_match('#(^|/)\.\.(/|$)#', $raw) || str_starts_with($raw, '/') || str_contains($raw, "\0")) {
                        throw new \RuntimeException(t('Unsafe path in package: {p}', ['p' => $raw]));
                    }
                    continue;   // directories, hidden files (.github, .gitignore …)
                }
                $top = explode('/', $n)[0];
                if ($n === 'setup.php' || $n === 'release.json' || !((in_array($top, self::DIRS, true) && str_contains($n, '/')) || in_array($n, self::FILES, true)) || !preg_match(self::EXT, $n)) {
                    $skipped++;   // config, uploads, tests, CI files … are not part of an update
                    continue;
                }
                $zip->getExternalAttributesIndex($i, $opsys, $attr);
                if ($opsys === \ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0170000) === 0120000) {
                    throw new \RuntimeException(t('Symbolic links are not allowed: {p}', ['p' => $n]));
                }
                if ($st['size'] > 25 * 1048576 || ($total += (int) $st['size']) > self::MAX_BYTES || count($hashes) >= self::MAX_FILES) {
                    throw new \RuntimeException(t('The package is unreasonably large.'));
                }
                $data = $zip->getFromIndex($i);
                if ($data === false) {
                    throw new \RuntimeException(t('Cannot read {p}', ['p' => $n]));
                }
                $out->addFromString($n, $data);
                $hashes[$n] = hash('sha256', $data);
                if ($n === 'core/Bootstrap.php' && preg_match("/define\('MCMS_VERSION', '([^']+)'\)/", $data, $mm)) {
                    $version = $mm[1];
                }
            }
            if ($version === null || !isset($hashes['index.php'])) {
                throw new \RuntimeException(t('The package does not contain the core files.'));
            }
            $notes = trim($l['message'] ?? '') !== '' ? (string) $l['message'] : (string) ($l['notes'] ?? '');
            $out->addFromString('release.json', json_encode([
                'name' => 'MagicCMS', 'version' => $version, 'commit' => $l['commit'], 'notes' => mb_substr($notes, 0, 4000), 'files' => $hashes,
                'source' => ['repo' => $l['repo'], 'ref' => $l['ref'], 'kind' => $l['kind'], 'skipped' => $skipped, 'built' => gmdate('c')],
            ], JSON_UNESCAPED_SLASHES));
            $out->close();
            $zip->close();
            @unlink($tmpSrc);
        } catch (\Throwable $e) {
            @$zip->close();
            @$out->close();
            @unlink($tmpSrc);
            @unlink($outPath);
            throw $e;
        }
        return $outPath;
    }
}
