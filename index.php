<?php
/** MagicCMS front controller – every request lands here. */
declare(strict_types=1);

// PHP built-in dev server: let real files (static assets, setup.php) through. Apache/nginx do this natively.
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $f = __DIR__ . $p;
    if ($p !== '/' && is_file($f) && ($f !== __FILE__) && !preg_match('#/(core|modules|panels|views|config|tools|backups)/#', $p)) {
        return false;
    }
}

define('MCMS_ROOT', __DIR__);
require __DIR__ . '/core/Bootstrap.php';
MagicCMS\Core\Bootstrap::run();
