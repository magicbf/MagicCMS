<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Sessions, login, registration helpers, role/permission checks. */
final class Auth
{
    private static ?array $user = null;
    private static ?array $perms = null;
    /** Set when a correct password was refused only because the e-mail address is not verified yet. */
    public static bool $needsVerify = false;

    private const USER_SQL = 'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.level AS role_level
                              FROM {users} u JOIN {roles} r ON r.id = u.role_id WHERE u.id = ?';

    public static function init(): void
    {
        if (!empty($_SESSION['uid'])) {
            $u = db()->one(self::USER_SQL, [(int) $_SESSION['uid']]);
            if ($u && $u['status'] === 'active' && self::sessionValid($u)) {
                self::$user = $u;
            } else {
                unset($_SESSION['uid']);
            }
        }
        if (!self::$user && !empty($_COOKIE['mcms_remember'])) {
            self::loginFromCookie((string) $_COOKIE['mcms_remember']);
        }
    }

    /** A session that started before the last password change is no longer valid. */
    private static function sessionValid(array $u): bool
    {
        return empty($u['pw_changed_at']) || strtotime($u['pw_changed_at'] . ' UTC') <= (int) ($_SESSION['auth_t'] ?? 0);
    }

    /** Call after a password was changed/reset: ends every other session and remember-me token of that user. */
    public static function passwordChanged(int $userId): void
    {
        db()->exec('UPDATE {users} SET pw_changed_at = ? WHERE id = ?', [now(), $userId]);
        db()->exec('DELETE FROM {remember_tokens} WHERE user_id = ?', [$userId]);
        if (self::$user && (int) self::$user['id'] === $userId) {
            $_SESSION['auth_t'] = time();
        }
    }

    /** Sliding-window abuse limiter backed by login_attempts (bucket = e.g. "reg", "pw"). */
    public static function limited(string $bucket, int $max, int $window = 900): bool
    {
        return (int) db()->val('SELECT COUNT(*) FROM {login_attempts} WHERE ip_hash = ? AND login = ? AND created_at > ?', [ip_hash(), 'b:' . $bucket, gmdate('Y-m-d H:i:s', time() - $window)]) >= $max;
    }

    public static function hit(string $bucket): void
    {
        db()->exec('INSERT INTO {login_attempts} (ip_hash, login, created_at) VALUES (?,?,?)', [ip_hash(), 'b:' . $bucket, now()]);
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function level(): int
    {
        return (int) (self::$user['role_level'] ?? 0);
    }

    /** Superadministrators pass every check; others need the permission on their role. */
    public static function can(string $perm): bool
    {
        if (self::level() >= 100) {
            return true;
        }
        if (self::$perms === null) {
            $role = self::$user['role_id'] ?? db()->val('SELECT id FROM {roles} WHERE slug = ?', ['guest']);
            self::$perms = array_column(db()->all(
                'SELECT p.perm_key FROM {role_permissions} rp JOIN {permissions} p ON p.id = rp.permission_id WHERE rp.role_id = ?',
                [$role]
            ), 'perm_key');
        }
        return in_array($perm, self::$perms, true);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
    }

    public static function roleId(string $slug): int
    {
        return (int) db()->val('SELECT id FROM {roles} WHERE slug = ?', [$slug]);
    }

    /** @return string|null error message, null on success */
    public static function attempt(string $login, string $password, bool $remember): ?string
    {
        $ip = ip_hash();
        $since = gmdate('Y-m-d H:i:s', time() - 900);
        // per connection: 10 failures / 15 min; per account (from anywhere): 30 – high enough that a stranger cannot lock the owner out
        $byIp = (int) db()->val("SELECT COUNT(*) FROM {login_attempts} WHERE created_at > ? AND ip_hash = ? AND login NOT LIKE 'b:%'", [$since, $ip]);
        $byLogin = (int) db()->val('SELECT COUNT(*) FROM {login_attempts} WHERE created_at > ? AND login = ?', [$since, mb_strtolower($login)]);
        if ($byIp >= 10 || $byLogin >= 30) {
            return t('Too many failed attempts. Please wait 15 minutes.');
        }

        self::$needsVerify = false;
        $u = db()->one('SELECT id, password_hash, status, email_verified_at FROM {users} WHERE username = ? OR email = ? LIMIT 1', [$login, $login]);
        // Always run a hash verification so timing does not reveal whether the account exists.
        $hash = $u['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$MUg4cEdFVGRhdmliY200OQ$DhlaQqPWPlpsyGWK0RmfYQCLLNUAag+YDy/VuavE3/8'; // valid dummy → same cost for unknown users
        $ok = password_verify($password, $hash);

        if (!$u || !$ok) {
            db()->exec('INSERT INTO {login_attempts} (ip_hash, login, created_at) VALUES (?,?,?)', [$ip, mb_strtolower($login), now()]);
            return t('Invalid username or password.');
        }
        if (setting('auth.email_verify') && empty($u['email_verified_at']) && $u['status'] !== 'banned') {
            self::$needsVerify = true;
            return t('Please verify your e-mail address first. We sent you a link when you registered.');
        }
        if ($u['status'] === 'pending') {
            return t('Your account is awaiting approval.');
        }
        if ($u['status'] !== 'active') {
            return t('This account has been disabled.');
        }

        if (password_needs_rehash($u['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            db()->exec('UPDATE {users} SET password_hash = ? WHERE id = ?', [self::hash($password), $u['id']]);
        }
        db()->exec('DELETE FROM {login_attempts} WHERE login = ?', [mb_strtolower($login)]);
        if (random_int(1, 50) === 1) {
            db()->exec('DELETE FROM {login_attempts} WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)]);
        }
        self::start((int) $u['id']);
        if ($remember) {
            self::issueRemember((int) $u['id']);
        }
        return null;
    }

    public static function start(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        $_SESSION['auth_t'] = time();
        unset($_SESSION['csrf']); // fresh CSRF token for the new session
        unset($_SESSION['online_ts']); // re-record presence as this user right away
        self::$perms = null;
        self::$user = db()->one(self::USER_SQL, [$userId]);
        db()->exec('UPDATE {users} SET last_login = ?, last_seen = ? WHERE id = ?', [now(), now(), $userId]);
    }

    public static function logout(): void
    {
        if (!empty($_COOKIE['mcms_remember'])) {
            [$sel] = array_pad(explode(':', (string) $_COOKIE['mcms_remember']), 2, '');
            db()->exec('DELETE FROM {remember_tokens} WHERE selector = ?', [$sel]);
            self::cookie('mcms_remember', '', time() - 3600);
        }
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
        self::$perms = null;
    }

    public static function cookie(string $name, string $value, int $expires): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => Bootstrap::$base ?: '/',
            'secure' => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function issueRemember(int $userId): void
    {
        $sel = bin2hex(random_bytes(9));
        $val = bin2hex(random_bytes(32));
        $exp = time() + 30 * 86400;
        db()->exec(
            'INSERT INTO {remember_tokens} (selector, validator_hash, user_id, expires_at) VALUES (?,?,?,?)',
            [$sel, hash('sha256', $val), $userId, gmdate('Y-m-d H:i:s', $exp)]
        );
        self::cookie('mcms_remember', $sel . ':' . $val, $exp);
    }

    private static function loginFromCookie(string $cookie): void
    {
        [$sel, $val] = array_pad(explode(':', $cookie, 2), 2, '');
        if (!preg_match('/^[a-f0-9]{18}$/', $sel) || !preg_match('/^[a-f0-9]{64}$/', $val)) {
            return;
        }
        $row = db()->one('SELECT * FROM {remember_tokens} WHERE selector = ?', [$sel]);
        if (!$row) {
            return;
        }
        db()->exec('DELETE FROM {remember_tokens} WHERE selector = ?', [$sel]);
        if (!hash_equals($row['validator_hash'], hash('sha256', $val))) {
            // Selector known but validator wrong → possible theft: revoke every token of this user.
            db()->exec('DELETE FROM {remember_tokens} WHERE user_id = ?', [$row['user_id']]);
            return;
        }
        if (strtotime($row['expires_at'] . ' UTC') < time()) {
            return;
        }
        $u = db()->one(self::USER_SQL, [(int) $row['user_id']]);
        if ($u && $u['status'] === 'active') {
            self::start((int) $u['id']);
            self::issueRemember((int) $u['id']);
        }
    }

    /** Roles the current user may assign (strictly below their own level; never superadmin). */
    public static function assignableRoles(): array
    {
        return db()->all(
            'SELECT * FROM {roles} WHERE level < ? AND level < 100 AND level > 0 ORDER BY level DESC',
            [self::level()]
        );
    }

    public static function userLevel(int $userId): int
    {
        return (int) db()->val('SELECT r.level FROM {users} u JOIN {roles} r ON r.id = u.role_id WHERE u.id = ?', [$userId]);
    }

    /**
     * Rank rule for moderating someone's CONTENT (posts, topics, comments, shouts):
     * you may act on content from users at or below your own rank, and on your own.
     * Consequence: moderators cannot touch admins, admins cannot touch the superadministrator in any way.
     */
    public static function mayAffect(int $targetUserId, ?int $targetLevel = null): bool
    {
        if (self::$user === null) {
            return false;
        }
        if ($targetUserId === (int) self::$user['id']) {
            return true;
        }
        return self::level() >= ($targetLevel ?? self::userLevel($targetUserId));
    }

    /** May the current user manage (edit role/status of) this target user? */
    public static function canManage(array $target): bool
    {
        return self::$user !== null
            && (int) $target['id'] !== (int) self::$user['id']
            && (int) $target['role_level'] < self::level();
    }
}
