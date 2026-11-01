<?php
/** Boots MagicCMS for command-line tools (no HTTP, no session). */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('MCMS_ROOT', dirname(__DIR__));
require MCMS_ROOT . '/core/Bootstrap.php';
MagicCMS\Core\Bootstrap::autoload();
if (!is_file(MCMS_ROOT . '/config/config.php')) {
    fwrite(STDERR, "MagicCMS is not installed.\n");
    exit(1);
}
MagicCMS\Core\Bootstrap::$config = require MCMS_ROOT . '/config/config.php';
MagicCMS\Core\Bootstrap::$db = new MagicCMS\Core\Database(MagicCMS\Core\Bootstrap::$config['db']);
MagicCMS\Core\Settings::load();
date_default_timezone_set('UTC');
