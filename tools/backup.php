<?php
/**
 * Command-line backup (cron-friendly):
 *   php tools/backup.php             database only (db-….sql.gz)
 *   php tools/backup.php --uploads   database + uploads (full-….zip)
 * Example crontab (daily 03:15):  15 3 * * *  php /path/to/site/tools/backup.php
 */
require __DIR__ . '/_boot.php';

use MagicCMS\Core\{Backup, Settings};

try {
    $r = Backup::create('cron', in_array('--uploads', $argv, true));
    Settings::set('backup.last_auto', time());
    echo "OK {$r['name']} ({$r['tables']} tables, {$r['rows']} rows, " . round($r['size'] / 1024) . " KB)\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Backup failed: ' . $e->getMessage() . "\n");
    exit(1);
}
