<?php
/**
 * Global template/controller helpers. Keep these tiny and dependency-free.
 */
declare(strict_types=1);

use MagicCMS\Core\{Auth, Bootstrap, Csrf, Hooks, Settings, View};

/** Escape for HTML output. Use on EVERY dynamic value in templates. */
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a URL relative to the install directory: url('forum/topic/1'). */
function url(string $path = ''): string
{
    return Bootstrap::$base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/')) . '?v=' . MCMS_VERSION;
}

/** Translate. The English text is the key; lang/<code>.php maps it to other languages. */
function t(string $s, array $repl = []): string
{
    static $lang = null;
    if ($lang === null) {
        $code = preg_replace('/[^a-z_\-]/i', '', (string) setting('site.lang', 'en'));
        $f = MCMS_ROOT . '/lang/' . $code . '.php';
        $lang = is_file($f) ? (array) require $f : [];
    }
    $out = $lang[$s] ?? $s;
    foreach ($repl as $k => $v) {
        $out = str_replace('{' . $k . '}', (string) $v, $out);
    }
    return $out;
}

function setting(string $key, $default = null)
{
    return Settings::get($key, $default);
}

function db(): MagicCMS\Core\Database
{
    return Bootstrap::$db;
}

function user(): ?array
{
    return Auth::user();
}

function can(string $perm): bool
{
    return Auth::can($perm);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/** Render a template (theme override → module view → core view). */
function render(string $name, array $data = []): string
{
    return View::render($name, $data);
}

/** Output injection point for plugins: echo hook('layout.render.head'); */
function hook(string $name, ...$args): string
{
    return Hooks::html($name, ...$args);
}

function theme_assets(string $where): string
{
    return View::assets($where);
}

function redirect(string $to, int $code = 302): never
{
    if (!preg_match('#^https?://#i', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $code);
    exit;
}

/** Only allow redirecting to local paths ("/foo"), never external hosts. */
function safe_next(?string $next, string $fallback = '/'): string
{
    if ($next && $next[0] === '/' && !str_starts_with($next, '//') && !str_contains($next, '\\')) {
        return $next;
    }
    return $fallback;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Trimmed request value (POST first, then GET). */
function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $v;
}

function json_out($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Current UTC time as a MySQL DATETIME string. All timestamps are stored in UTC. */
function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function fmt_date(?string $utc, string $fmt = 'Y-m-d H:i'): string
{
    if (!$utc) {
        return '—';
    }
    $tz = (string) (user()['timezone'] ?? '') ?: (string) setting('site.timezone', 'UTC');
    try {
        $d = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        return $d->setTimezone(new DateTimeZone($tz))->format($fmt);
    } catch (Throwable) {
        return $utc;
    }
}

function time_ago(?string $utc): string
{
    if (!$utc) {
        return t('never');
    }
    $diff = max(0, time() - (int) strtotime($utc . ' UTC'));
    return match (true) {
        $diff < 60 => t('just now'),
        $diff < 3600 => t('{n} min ago', ['n' => intdiv($diff, 60)]),
        $diff < 86400 => t('{n} h ago', ['n' => intdiv($diff, 3600)]),
        default => t('{n} d ago', ['n' => intdiv($diff, 86400)]),
    };
}

/** Avatar URL for a user row (needs id, avatar). Falls back to a generated SVG. */
function avatar_url(array $u, int $size = 64): string
{
    if (!empty($u['avatar'])) {
        $s = 256;
        foreach ([32, 64, 128, 256] as $cand) {
            if ($cand >= $size) {
                $s = $cand;
                break;
            }
        }
        return url('uploads/avatars/' . preg_replace('/\.(\w+)$/', '_' . $s . '.$1', (string) $u['avatar']));
    }
    return url('avatar/' . (int) ($u['id'] ?? 0) . '.svg');
}

/** Write an entry to the audit log. */
function audit(string $action, string $detail = ''): void
{
    try {
        db()->exec(
            'INSERT INTO {audit_log} (user_id, action, detail, ip_hash, created_at) VALUES (?,?,?,?,?)',
            [user()['id'] ?? null, $action, mb_substr($detail, 0, 500), ip_hash(), now()]
        );
    } catch (Throwable) {
    }
}

function ip_hash(): string
{
    return hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', (string) (Bootstrap::$config['app']['secret'] ?? ''));
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

/** Validate #rrggbb. */
function is_hex(string $c): bool
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $c);
}

/** URL of the site logo: admin-uploaded file or the bundled mark. */
function site_logo(): string
{
    $f = (string) setting('site.logo', '');
    if ($f !== '' && preg_match('/^logo_[a-f0-9]{12}\.(png|svg)$/', $f) && is_file(MCMS_ROOT . '/uploads/branding/' . $f)) {
        return url('uploads/branding/' . $f);
    }
    return asset('logo-mark.svg');
}

function has_custom_logo(): bool
{
    return site_logo() !== asset('logo-mark.svg');
}
