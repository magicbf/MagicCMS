<?php
declare(strict_types=1);

namespace MagicCMS\Core;

define('MCMS_VERSION', '0.1.3');

/** Application entry: autoload → config → session → DB → auth → modules → router. */
final class Bootstrap
{
    public static string $base = '';
    public static array $config = [];
    public static Database $db;
    public static string $nonce = '';

    public static function autoload(): void
    {
        spl_autoload_register(static function (string $class): void {
            if (str_starts_with($class, 'MagicCMS\\Core\\')) {
                $f = MCMS_ROOT . '/core/' . substr($class, 14) . '.php';
            } elseif (preg_match('/^MagicCMS\\\\Modules\\\\(\w+)\\\\(\w+)$/', $class, $m)) {
                $f = MCMS_ROOT . '/modules/' . strtolower($m[1]) . '/' . $m[2] . '.php';
            } elseif (preg_match('/^MagicCMS\\\\Plugins\\\\(\w+)\\\\(\w+)$/', $class, $m)) {
                $f = MCMS_ROOT . '/plugins/' . strtolower($m[1]) . '/' . $m[2] . '.php';
            } else {
                return;
            }
            if (is_file($f)) {
                require $f;
            }
        });
        require_once MCMS_ROOT . '/core/helpers.php';
    }

    public static function run(): void
    {
        self::autoload();
        self::$base = PHP_SAPI === 'cli-server' ? '' : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if (!is_file(MCMS_ROOT . '/config/installed.lock') || !is_file(MCMS_ROOT . '/config/config.php')) {
            if (is_file(MCMS_ROOT . '/setup.php')) {
                header('Location: ' . self::$base . '/setup.php');
                exit;
            }
            http_response_code(503);
            exit('MagicCMS is not installed (setup.php missing).');
        }

        self::$config = require MCMS_ROOT . '/config/config.php';
        $debug = !empty(self::$config['app']['debug']);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
        date_default_timezone_set('UTC');

        set_exception_handler(static function (\Throwable $e) use ($debug): void {
            error_log('MagicCMS: ' . $e);
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo '<!doctype html><meta charset="utf-8"><title>Error</title><body style="font:16px system-ui;background:#0b0b0d;color:#eee;padding:3rem">'
                . '<h1 style="color:#ff4d4d">Something went wrong</h1><p>The error has been logged.</p>'
                . ($debug ? '<pre style="white-space:pre-wrap">' . htmlspecialchars((string) $e) . '</pre>' : '');
        });

        self::$nonce = base64_encode(random_bytes(16));
        self::headers();
        self::session();
        self::$db = new Database(self::$config['db']);
        Settings::load();
        if (Migrator::latest() > (int) Settings::get('schema.version', 1)) { // new code, older DB: upgrade automatically
            try {
                Migrator::upgrade(self::$db);
            } catch (\Throwable $e) {
                // a freshly installed update whose migrations fail is rolled back (code + database)
                if (class_exists(Updater::class) && Updater::rollbackPending($e)) {
                    http_response_code(503);
                    exit('<!doctype html><meta charset="utf-8"><body style="font:16px system-ui;padding:3rem"><h1>Update rolled back</h1><p>The update could not be completed and the previous version and data were restored. Details are in the server error log.</p><p><a href="">Reload</a></p>');
                }
                throw $e;
            }
            Settings::load();
        }
        Auth::init();
        // opportunistic automatic backup: only when an administrator is browsing the admin area, after the page was sent
        if (Auth::level() >= 100 && Backup::autoDue() && str_starts_with((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), self::$base . '/admin') && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
            register_shutdown_function(static function (): void {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                Backup::autoRun();
            });
        }

        $router = new Router();
        self::loadModules($router);
        self::trackOnline();

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (self::$base !== '' && str_starts_with($path, self::$base)) {
            $path = substr($path, strlen(self::$base));
        }
        $path = preg_replace('#/index\.php(?=/|$)#', '', $path) ?: '/';

        if (setting('site.maintenance') && !can('admin.access') && !preg_match('#^/(login|logout|assets)#', $path)) {
            http_response_code(503);
            header('Retry-After: 600');
            echo View::page(t('Maintenance'), render('message', ['title' => t('Maintenance'), 'text' => t('The site is temporarily offline. Please check back soon.')]), ['layout' => 'none']);
            return;
        }

        // PHP discards the whole body when it exceeds post_max_size → give a clear message instead of a CSRF error.
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !$_POST && !$_FILES && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            http_response_code(413);
            echo View::page(t('Upload too large'), render('message', ['title' => t('Upload too large'), 'text' => t('The data you sent exceeds the server limit ({limit}). Please upload a smaller file.', ['limit' => ini_get('post_max_size')])]), ['layout' => 'none']);
            return;
        }

        $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
    }

    private static function headers(): void
    {
        $n = self::$nonce;
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'nonce-$n'; style-src-attr 'unsafe-inline'; script-src 'self' 'nonce-$n'; base-uri 'self'; form-action 'self'; frame-src https://www.youtube-nocookie.com; frame-ancestors 'self'");
        if (is_https()) {
            header('Strict-Transport-Security: max-age=15552000');
        }
    }

    private static function session(): void
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('mcms_sid');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => self::$base ?: '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** Enabled modules (modules table) that exist on disk register their routes. */
    private static function loadModules(Router $router): void
    {
        foreach (db()->all('SELECT module_key, is_core FROM {modules} WHERE enabled = 1 ORDER BY id') as $m) {
            $dir = MCMS_ROOT . ($m['is_core'] ? '/modules/' : '/plugins/') . $m['module_key'];
            if (preg_match('/^[a-z0-9_]+$/', $m['module_key']) && is_file($dir . '/routes.php')) {
                (require $dir . '/routes.php')($router);
            }
        }
    }

    /** "Who is online": one upsert per visitor per minute. */
    private static function trackOnline(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || str_contains($_SERVER['REQUEST_URI'] ?? '', '/chat/')) {
            return;
        }
        if (empty($_COOKIE[session_name()])) {
            return; // cookie-less clients (bots, scrapers) are not tracked: one session per request must not become one DB write per request
        }
        if (time() - (int) ($_SESSION['online_ts'] ?? 0) < 60) {
            return;
        }
        $_SESSION['online_ts'] = time();
        $loc = mb_substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', 0, 190);
        db()->exec(
            'INSERT INTO {online_sessions} (sid_hash, user_id, last_seen, location) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), last_seen = VALUES(last_seen), location = VALUES(location)',
            [hash('sha256', session_id()), user()['id'] ?? null, now(), $loc]
        );
        if (user()) {
            db()->exec('UPDATE {users} SET last_seen = ? WHERE id = ?', [now(), user()['id']]);
        }
        if (random_int(1, 40) === 1) {
            db()->exec('DELETE FROM {online_sessions} WHERE last_seen < ?', [gmdate('Y-m-d H:i:s', time() - 3600)]);
        }
    }
}
