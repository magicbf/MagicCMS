<?php
/**
 * Command-line update:
 *   php tools/update.php path/to/MagicCMS-1.2.0.zip     validate, back up, install, migrate
 *   php tools/update.php --rollback                      undo an unfinished / the last update
 * Settings, content, uploads and config are never touched.
 */
require __DIR__ . '/_boot.php';

use MagicCMS\Core\{Migrator, Settings, Updater};

$arg = $argv[1] ?? '';
try {
    if ($arg === '--finish') { // runs in a fresh process, so the NEW code is loaded
        try {
            $v = Migrator::upgrade(MagicCMS\Core\Bootstrap::$db);
            Settings::load();
            Updater::complete();
            echo "Migrations done (schema $v). Update complete – MagicCMS v" . MCMS_VERSION . "\n";
            exit(0);
        } catch (Throwable $e) {
            $ok = Updater::rollbackPending($e);
            fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n" . ($ok ? "Rolled back to the previous version and data.\n" : "Rollback FAILED – restore backups/ manually.\n"));
            exit(1);
        }
    }
    if ($arg === '--rollback') {
        $m = Updater::pending() ?: (function () {
            $h = ((array) setting('update.history', []))[0] ?? null;
            return $h ? ['codeBackup' => $h['codeBackup'], 'dbBackup' => $h['dbBackup'], 'created' => [], 'maintenance' => false] : null;
        })();
        if (!$m) {
            fwrite(STDERR, "Nothing to roll back.\n");
            exit(1);
        }
        Updater::rollback($m);
        echo "Rolled back: code and database restored.\n";
        exit(0);
    }
    if ($arg === '' || !is_file($arg)) {
        fwrite(STDERR, "Usage: php tools/update.php <package.zip> | --rollback\n");
        exit(2);
    }
    $insp = Updater::inspect($arg);
    foreach ($insp['warnings'] as $w) {
        echo "Warning: $w\n";
    }
    if (!$insp['ok']) {
        fwrite(STDERR, "Package rejected:\n - " . implode("\n - ", $insp['errors']) . "\n");
        exit(1);
    }
    echo "Updating v" . MCMS_VERSION . " → v{$insp['version']} ({$insp['replace']} files replaced, {$insp['new']} new)\n";
    $r = Updater::apply($arg);
    echo "Files installed. Backups: {$r['dbBackup']}, {$r['codeBackup']}\n";
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --finish', $code);
    exit($code);
} catch (Throwable $e) {
    fwrite(STDERR, 'Update failed: ' . $e->getMessage() . "\n");
    exit(1);
}
