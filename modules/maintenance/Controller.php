<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Maintenance;

use MagicCMS\Core\{Backup, Settings, Updater, View};

/** Admin → Backups & updates (superadministrator only). */
final class Controller
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'maintenance', 'title' => $title, 'inner' => render('maintenance/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    private function back(string $to = '/admin/maintenance'): never
    {
        redirect($to);
    }

    // ------------------------------------------------------------------ backups
    public function index(): string
    {
        return $this->page(t('Backups & updates'), 'index', [
            'backups' => Backup::all(), 'dir' => Backup::dir(), 'pending' => Updater::pending(),
            'zip' => class_exists(\ZipArchive::class), 'writable' => is_writable(Backup::dir()),
            'protected' => is_file(Backup::dir() . '/.htaccess') || !str_starts_with(Backup::dir(), MCMS_ROOT),
        ]);
    }

    public function create(): never
    {
        try {
            $r = Backup::create('manual', input('uploads') === '1');
            audit('backup.create', $r['name']);
            flash('success', t('Backup created: {n} ({s}, {t} tables, {r} rows).', ['n' => $r['name'], 's' => self::size($r['size']), 't' => $r['tables'], 'r' => $r['rows']]));
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
        }
        $this->back();
    }

    public static function size(int $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, (int) round($b / 1024)) . ' KB';
    }

    public function download(): never
    {
        $p = Backup::path((string) input('name'));
        if (!$p) {
            flash('danger', t('Backup not found.'));
            $this->back();
        }
        audit('backup.download', basename($p));
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($p) . '"');
        header('Content-Length: ' . filesize($p));
        header('X-Content-Type-Options: nosniff');
        readfile($p);
        exit;
    }

    public function delete(): never
    {
        if (Backup::delete((string) input('name'))) {
            audit('backup.delete', (string) input('name'));
            flash('success', t('Backup deleted.'));
        }
        $this->back();
    }

    public function restore(): never
    {
        if (strtoupper((string) input('confirm')) !== 'RESTORE') {
            flash('danger', t('Type RESTORE to confirm.'));
            $this->back();
        }
        try {
            $r = Backup::restore((string) input('name'));
            audit('backup.restore', (string) input('name'));
            flash('success', t('Restored {n} statements{f}. A safety backup of the previous state was saved ({s}).', ['n' => $r['statements'], 'f' => $r['files'] ? ' + ' . $r['files'] . ' ' . t('files') : '', 's' => $r['safety']]));
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
        }
        $this->back();
    }

    /** Upload a database backup (.sql.gz) made elsewhere. */
    public function upload(): never
    {
        $f = $_FILES['backup'] ?? null;
        if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            flash('danger', t('Upload failed (check the server upload limit: {l}).', ['l' => ini_get('upload_max_filesize')]));
            $this->back();
        }
        $tmp = Backup::dir() . '/.upload-' . bin2hex(random_bytes(4)) . '.sql.gz';
        move_uploaded_file($f['tmp_name'], $tmp);
        if ($err = Backup::verify($tmp)) {
            @unlink($tmp);
            flash('danger', $err);
            $this->back();
        }
        $name = 'db-' . gmdate('Ymd-His') . '-uploaded.sql.gz';
        rename($tmp, Backup::dir() . '/' . $name);
        audit('backup.upload', $name);
        flash('success', t('Backup uploaded as {n}. Use “Restore” to apply it.', ['n' => $name]));
        $this->back();
    }

    public function settings(): never
    {
        Settings::set('backup.auto_days', max(0, min(90, (int) input('auto_days', 7))));
        Settings::set('backup.keep', max(1, min(100, (int) input('keep', 10))));
        $feed = trim((string) input('feed'));
        Settings::set('update.feed', $feed !== '' && preg_match('#^https://[^\s]+$#i', $feed) ? $feed : 'https://api.github.com/repos/magicbf/MagicCMS/releases/latest');
        flash('success', t('Settings saved.'));
        $this->back();
    }

    // ------------------------------------------------------------------ updates
    public function update(): string
    {
        $latest = $_SESSION['upd_latest'] ?? null;
        return $this->page(t('Updates'), 'update', [
            'latest' => $latest, 'pending' => Updater::pending(), 'history' => (array) setting('update.history', []),
            'modified' => Updater::locallyModified(), 'zip' => class_exists(\ZipArchive::class),
            'codeBackups' => array_values(array_filter(array_map('basename', glob(Backup::dir() . '/code-*.zip') ?: []))),
        ]);
    }

    public function check(): never
    {
        try {
            $_SESSION['upd_latest'] = Updater::latest();
            flash('success', $_SESSION['upd_latest']['newer'] ? t('Version {v} is available.', ['v' => $_SESSION['upd_latest']['version']]) : t('You are up to date.'));
        } catch (\Throwable $e) {
            unset($_SESSION['upd_latest']);
            flash('danger', $e->getMessage());
        }
        $this->back('/admin/maintenance/update');
    }

    /** Get a package (upload or download), validate it and show the review page. */
    public function prepare(): never
    {
        $tmp = null;
        try {
            if (input('source') === 'online') {
                $l = $_SESSION['upd_latest'] ?? null;
                if (!$l || !$l['url']) {
                    throw new \RuntimeException(t('Check for updates first (and make sure the release has a zip file).'));
                }
                $tmp = Updater::download($l['url'], $l['sha256']);
                $_SESSION['upd_checksum'] = $l['sha256'] !== '';
            } else {
                $f = $_FILES['package'] ?? null;
                if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                    throw new \RuntimeException(t('Upload failed (check the server upload limit: {l}).', ['l' => ini_get('upload_max_filesize')]));
                }
                $tmp = Backup::dir() . '/.incoming-' . bin2hex(random_bytes(6)) . '.zip';
                move_uploaded_file($f['tmp_name'], $tmp);
                $_SESSION['upd_checksum'] = false;
            }
            $_SESSION['upd_file'] = basename($tmp);
            $this->back('/admin/maintenance/update/review');
        } catch (\Throwable $e) {
            if ($tmp) {
                @unlink($tmp);
            }
            flash('danger', $e->getMessage());
            $this->back('/admin/maintenance/update');
        }
    }

    private function incoming(): ?string
    {
        $n = (string) ($_SESSION['upd_file'] ?? '');
        $p = Backup::dir() . '/' . $n;
        return preg_match('/^\.incoming-[a-f0-9]{12}\.zip$/', $n) && is_file($p) ? $p : null;
    }

    public function review(): string
    {
        $p = $this->incoming();
        if (!$p) {
            flash('danger', t('No update package prepared.'));
            $this->back('/admin/maintenance/update');
        }
        $insp = Updater::inspect($p);
        $pre = $insp['ok'] ? Updater::preflight($insp, $p) : [];
        return $this->page(t('Review update'), 'review', ['insp' => $insp, 'preflight' => $pre, 'checksum' => !empty($_SESSION['upd_checksum']), 'pending' => Updater::pending()]);
    }

    public function apply(): never
    {
        $p = $this->incoming();
        if (!$p || strtoupper((string) input('confirm')) !== 'UPDATE') {
            flash('danger', $p ? t('Type UPDATE to confirm.') : t('No update package prepared.'));
            $this->back($p ? '/admin/maintenance/update/review' : '/admin/maintenance/update');
        }
        try {
            $r = Updater::apply($p);
            audit('update.apply', $r['from'] . ' → ' . $r['to']);
            @unlink($p);
            unset($_SESSION['upd_file'], $_SESSION['upd_latest']);
            $_SESSION['upd_result'] = $r;
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
            $this->back('/admin/maintenance/update/review');
        }
        // the NEW code serves this request: it runs the database migrations first
        $this->back('/admin/maintenance/update/finish');
    }

    public function finish(): string
    {
        $m = Updater::complete();
        $r = $_SESSION['upd_result'] ?? null;
        unset($_SESSION['upd_result']);
        return $this->page(t('Update finished'), 'finish', ['m' => $m, 'r' => $r, 'schema' => \MagicCMS\Core\Migrator::current(db()), 'latest' => \MagicCMS\Core\Migrator::latest()]);
    }

    public function rollback(): never
    {
        if (strtoupper((string) input('confirm')) !== 'ROLLBACK') {
            flash('danger', t('Type ROLLBACK to confirm.'));
            $this->back('/admin/maintenance/update');
        }
        $m = Updater::pending();
        if (!$m) {
            $h = ((array) setting('update.history', []))[0] ?? null;
            if (!$h) {
                flash('danger', t('There is no update to undo.'));
                $this->back('/admin/maintenance/update');
            }
            $m = ['codeBackup' => $h['codeBackup'], 'dbBackup' => $h['dbBackup'], 'created' => [], 'maintenance' => false];
        }
        try {
            Updater::rollback($m);
            Settings::set('update.history', array_slice((array) setting('update.history', []), 1));
            flash('success', t('Rolled back: code and database are as before the update.'));
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
        }
        $this->back('/admin/maintenance/update');
    }
}
