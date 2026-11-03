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
        flash('success', t('Settings saved.'));
        $this->back();
    }

    // ------------------------------------------------------------------ updates
    public function update(): string
    {
        Updater::cleanStaging();
        return $this->page(t('Updates'), 'update', [
            'latest' => $_SESSION['upd_latest'] ?? null, 'pending' => Updater::pending(), 'history' => (array) setting('update.history', []),
            'modified' => Updater::locallyModified(), 'zip' => class_exists(\ZipArchive::class), 'curl' => function_exists('curl_init'),
            'branches' => (array) ($_SESSION['upd_branches'] ?? []), 'repo' => Updater::repo(), 'channel' => Updater::channel(), 'installed' => Updater::installedCommit(),
            'autoCheck' => (bool) setting('update.auto_check', true), 'customFeed' => Updater::feedUrl() !== 'https://api.github.com/repos/magicbf/MagicCMS/releases/latest',
            'codeBackups' => array_values(array_filter(array_map('basename', glob(Backup::dir() . '/code-*.zip') ?: []))),
        ]);
    }

    public function check(): never
    {
        if (time() - (int) ($_SESSION['upd_checked_at'] ?? 0) < 8) {   // GitHub allows only 60 anonymous requests an hour
            flash('warning', t('Please wait a few seconds between checks.'));
            $this->back('/admin/maintenance/update');
        }
        $_SESSION['upd_checked_at'] = time();
        try {
            $l = Updater::latest();
            $_SESSION['upd_latest'] = $l;
            Updater::remember($l);
            flash('success', $l['newer'] ? ($l['unknown'] && $l['kind'] === 'branch' ? t('Latest build {v} found – compare it with this site on the next page.', ['v' => $l['short']]) : t('An update is available: {v}.', ['v' => $l['kind'] === 'branch' ? 'v' . $l['version'] . ' · ' . $l['short'] : 'v' . $l['version']])) : t('You are up to date.'));
        } catch (\Throwable $e) {
            unset($_SESSION['upd_latest']);
            flash('danger', $e->getMessage());
        }
        $this->back('/admin/maintenance/update');
    }

    /** Where updates come from (repository + channel). */
    public function sourceSave(): never
    {
        $repo = trim((string) input('repo'));
        if (!preg_match('#^[A-Za-z0-9_.\-]{1,39}/[A-Za-z0-9_.\-]{1,100}$#', $repo)) {
            flash('danger', t('The repository must look like owner/name.'));
            $this->back('/admin/maintenance/update');
        }
        $ch = (string) input('channel', 'auto');
        if ($ch === 'branch') {
            $b = trim((string) (input('branch_custom') ?: input('branch')));
            $ch = preg_match('#^[A-Za-z0-9._/\-]{1,100}$#', $b) && !str_contains($b, '..') ? 'branch:' . $b : 'auto';
        } elseif (!in_array($ch, ['auto', 'release'], true)) {
            $ch = 'auto';
        }
        Settings::set('update.repo', $repo);
        Settings::set('update.channel', $ch);
        Settings::set('update.feed', 'https://api.github.com/repos/magicbf/MagicCMS/releases/latest');   // the GitHub source replaces a custom feed
        Settings::set('update.auto_check', !empty($_POST['auto_check']));
        unset($_SESSION['upd_latest'], $_SESSION['upd_branches']);
        audit('update.source', $repo . ' ' . $ch);
        flash('success', t('Update source saved. Check for updates to use it.'));
        $this->back('/admin/maintenance/update');
    }

    public function branches(): never
    {
        try {
            $_SESSION['upd_branches'] = Updater::branches();
            flash('success', t('{n} branch(es) found.', ['n' => count($_SESSION['upd_branches'])]));
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
        }
        $this->back('/admin/maintenance/update');
    }

    /** Get a package (GitHub download or upload) into the private staging folder, validate it and show the review page. Nothing is installed here. */
    public function prepare(): never
    {
        $tmp = null;
        $this->discard();   // only one prepared package at a time
        try {
            if (input('source') === 'online') {
                $l = $_SESSION['upd_latest'] ?? null;
                if (!is_array($l)) {
                    throw new \RuntimeException(t('Check for updates first.'));
                }
                $tmp = Updater::download($l);
                $_SESSION['upd_meta'] = ['source' => $l, 'checksum' => ($l['sha256'] ?? '') !== ''];
            } else {
                $f = $_FILES['package'] ?? null;
                if (!$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                    throw new \RuntimeException(t('Upload failed (check the server upload limit: {l}).', ['l' => ini_get('upload_max_filesize')]));
                }
                $tmp = Backup::dir() . '/.incoming-' . bin2hex(random_bytes(6)) . '.zip';
                move_uploaded_file($f['tmp_name'], $tmp);
                $_SESSION['upd_meta'] = ['source' => null, 'checksum' => false];
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

    private function discard(): void
    {
        if ($old = $this->incoming()) {
            @unlink($old);
        }
        unset($_SESSION['upd_file'], $_SESSION['upd_meta']);
    }

    public function cancel(): never
    {
        $this->discard();
        flash('success', t('Nothing was installed. The downloaded package was deleted.'));
        $this->back('/admin/maintenance/update');
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
        $meta = (array) ($_SESSION['upd_meta'] ?? []);
        return $this->page(t('Review update'), 'review', [
            'insp' => $insp, 'preflight' => $pre, 'meta' => $meta, 'pending' => Updater::pending(), 'sha256' => (string) hash_file('sha256', $p), 'size' => (int) filesize($p),
            'installed' => Updater::installedCommit(), 'commits' => (array) (($meta['source']['commits'] ?? [])),
        ]);
    }

    /** Wrong-password throttle: 5 attempts per 10 minutes. */
    private function passwordOk(): bool
    {
        $now = time();
        $fails = array_values(array_filter((array) ($_SESSION['upd_pw_fail'] ?? []), static fn($t) => $now - (int) $t < 600));
        if (count($fails) >= 5) {
            flash('danger', t('Too many wrong passwords. Please wait 10 minutes.'));
            return false;
        }
        $hash = (string) db()->val('SELECT password_hash FROM {users} WHERE id = ?', [(int) user()['id']]);
        if ($hash !== '' && password_verify((string) ($_POST['password'] ?? ''), $hash)) {
            unset($_SESSION['upd_pw_fail']);
            return true;
        }
        $fails[] = $now;
        $_SESSION['upd_pw_fail'] = $fails;
        flash('danger', t('That password is not correct.'));
        return false;
    }

    public function apply(): never
    {
        $p = $this->incoming();
        if (!$p) {
            flash('danger', t('No update package prepared.'));
            $this->back('/admin/maintenance/update');
        }
        $back = '/admin/maintenance/update/review';
        if (strtoupper((string) input('confirm')) !== 'UPDATE') {
            flash('danger', t('Type UPDATE to confirm.'));
            $this->back($back);
        }
        if (empty($_POST['understand'])) {
            flash('danger', t('Please tick the box to confirm that you have read the review.'));
            $this->back($back);
        }
        if (!$this->passwordOk()) {
            $this->back($back);
        }
        try {
            $r = Updater::apply($p);
            audit('update.apply', $r['from'] . ' → ' . $r['to'] . ($r['commit'] ? ' @' . substr($r['commit'], 0, 7) : ''));
            @unlink($p);
            unset($_SESSION['upd_file'], $_SESSION['upd_latest'], $_SESSION['upd_meta']);
            $_SESSION['upd_result'] = $r;
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
            $this->back($back);
        }
        // the NEW code serves this request: it runs the database migrations first
        $this->back('/admin/maintenance/update/finish');
    }

    /** The package changes nothing (every file already matches): just remember which build this site is. */
    public function markCurrent(): never
    {
        $p = $this->incoming();
        $meta = (array) ($_SESSION['upd_meta'] ?? []);
        $c = (string) ($meta['source']['commit'] ?? '');
        if ($p && preg_match('/^[a-f0-9]{40}$/', $c)) {
            $insp = Updater::inspect($p);
            if ($insp['ok'] && !$insp['todo'] && !$insp['obsolete']) {
                Settings::set('update.commit', $c);
                Updater::remember(['newer' => false, 'short' => substr($c, 0, 7), 'version' => $insp['version'], 'ref' => (string) ($meta['source']['ref'] ?? ''), 'kind' => (string) ($meta['source']['kind'] ?? '')]);
                audit('update.mark_current', substr($c, 0, 7));
                flash('success', t('This site already matches build {c}. Nothing had to be installed.', ['c' => substr($c, 0, 7)]));
            }
        }
        $this->discard();
        unset($_SESSION['upd_latest']);
        $this->back('/admin/maintenance/update');
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
        $h = ((array) setting('update.history', []))[0] ?? null;
        $fromHistory = !$m;
        if (!$m) {
            if (!$h) {
                flash('danger', t('There is no update to undo.'));
                $this->back('/admin/maintenance/update');
            }
            $m = ['codeBackup' => $h['codeBackup'], 'dbBackup' => $h['dbBackup'], 'created' => $h['created'] ?? [], 'maintenance' => false];
        }
        try {
            Updater::rollback($m);
            if ($fromHistory && $h) {
                Settings::set('update.commit', (string) ($h['prevCommit'] ?? ''));   // the installed build is the previous one again
                Settings::set('update.history', array_slice((array) setting('update.history', []), 1));
            }
            Settings::set('update.status', []);
            audit('update.rollback', (string) ($h['to'] ?? ''));
            flash('success', t('Rolled back: code and database are as before the update.'));
        } catch (\Throwable $e) {
            flash('danger', $e->getMessage());
        }
        $this->back('/admin/maintenance/update');
    }
}
