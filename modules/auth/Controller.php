<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Auth;

use MagicCMS\Core\{Auth, Mailer, Tokens, View};

final class Controller
{
    public function loginForm(): string
    {
        if (user()) {
            redirect('/');
        }
        return View::page(t('Login'), render('auth/login', ['next' => safe_next(input('next'), ''), 'old' => '', 'verify' => false]), ['class' => 'page-auth']);
    }

    public function login(): string
    {
        $login = (string) input('login');
        $next = safe_next(input('next'), '/');
        if ($err = Auth::attempt($login, (string) ($_POST['password'] ?? ''), !empty($_POST['remember']))) {
            http_response_code(401);
            flash('danger', $err);
            return View::page(t('Login'), render('auth/login', ['next' => $next === '/' ? '' : $next, 'old' => $login, 'verify' => Auth::$needsVerify]), ['class' => 'page-auth']);
        }
        flash('success', t('Welcome back, {name}!', ['name' => user()['username']]));
        redirect($next);
    }

    public function registerForm(): string
    {
        if (user()) {
            redirect('/');
        }
        return View::page(t('Register'), render('auth/register', ['f' => [], 'mode' => setting('auth.registration', 'open')]), ['class' => 'page-auth']);
    }

    public function register(): string
    {
        $mode = (string) setting('auth.registration', 'open');
        if ($mode === 'closed' || user()) {
            redirect('/');
        }
        $f = ['username' => (string) input('username'), 'email' => (string) input('email')];
        $pw = (string) ($_POST['password'] ?? '');
        $err = [];

        if (!empty($_POST['website'])) { // honeypot: humans never fill this hidden field
            redirect('/');
        }
        if (!preg_match('/^[A-Za-z0-9_\-]{3,20}$/', $f['username'])) {
            $err[] = t('Username: 3–20 characters, letters, numbers, _ and - only.');
        }
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 190) {
            $err[] = t('Please enter a valid email address.');
        }
        if (strlen($pw) < 8 || strlen($pw) > 72) {
            $err[] = t('Password must be 8–72 characters.');
        }
        if ($pw !== (string) ($_POST['password2'] ?? '')) {
            $err[] = t('The passwords do not match.');
        }
        if (empty($_POST['tos'])) {
            $err[] = t('Please accept the rules to register.');
        }
        if (!$err && db()->val('SELECT id FROM {users} WHERE username = ? OR email = ?', [$f['username'], $f['email']])) {
            $err[] = t('That username or email is already registered.');
        }
        if ($err) {
            http_response_code(422);
            foreach ($err as $m) {
                flash('danger', $m);
            }
            return View::page(t('Register'), render('auth/register', ['f' => $f, 'mode' => $mode]), ['class' => 'page-auth']);
        }

        $status = $mode === 'approval' ? 'pending' : 'active';
        $id = db()->insert(
            'INSERT INTO {users} (username, email, password_hash, role_id, status, timezone, created_at) VALUES (?,?,?,?,?,?,?)',
            [$f['username'], $f['email'], Auth::hash($pw), Auth::roleId('member'), $status, null, now()]
        );
        \MagicCMS\Core\Hooks::fire('user.registered', ['id' => $id, 'username' => $f['username'], 'email' => $f['email']]);
        if (setting('auth.email_verify')) {
            self::sendVerification($id, $f['username'], $f['email']);
            flash('info', t('Almost done! We sent a confirmation link to {email}. Click it to activate your account.', ['email' => $f['email']]));
            redirect('/login');
        }
        if ($status === 'pending') {
            flash('info', t('Thanks! An administrator must approve your account before you can log in.'));
            redirect('/login');
        }
        Auth::start($id);
        flash('success', t('Welcome to {site}, {name}!', ['site' => setting('site.name'), 'name' => $f['username']]));
        redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('info', t('You have been logged out.'));
        redirect('/');
    }

    // ------------------------------------------------------------------ e-mail verification
    private static function throttled(string $kind, int $max = 5): bool
    {
        $ip = hash_hmac('sha256', 'mail|' . ($_SERVER['REMOTE_ADDR'] ?? ''), (string) (\MagicCMS\Core\Bootstrap::$config['app']['secret'] ?? ''));
        $n = (int) db()->val('SELECT COUNT(*) FROM {login_attempts} WHERE ip_hash = ? AND created_at > ?', [$ip, gmdate('Y-m-d H:i:s', time() - 900)]);
        if ($n >= $max) {
            return true;
        }
        db()->exec('INSERT INTO {login_attempts} (ip_hash, login, created_at) VALUES (?,?,?)', [$ip, 'mail:' . $kind, now()]);
        return false;
    }

    public static function sendVerification(int $userId, string $username, string $email, string $type = 'verify', ?string $newEmail = null): bool
    {
        $token = Tokens::create($userId, $type, 86400, $newEmail);
        $link = abs_url('verify/' . $token);
        $html = Mailer::template(t('Confirm your e-mail address'), '<p>' . e(t('Hi {name}, please confirm your e-mail address to finish setting up your account. The link is valid for 24 hours.', ['name' => $username])) . '</p>', [t('Confirm e-mail'), $link]);
        return Mailer::send($newEmail ?? $email, t('Confirm your e-mail address'), $html);
    }

    public function verify(array $p): string
    {
        foreach (['verify', 'email_change'] as $type) {
            if ($row = Tokens::peek($p['token'], $type)) {
                if ($type === 'email_change' && $row['new_email']) {
                    if (db()->val('SELECT id FROM {users} WHERE email = ? AND id <> ?', [$row['new_email'], $row['user_id']])) {
                        Tokens::burn((int) $row['id']);
                        flash('danger', t('That e-mail address is already in use.'));
                        redirect('/login');
                    }
                    db()->exec('UPDATE {users} SET email = ?, email_verified_at = ? WHERE id = ?', [$row['new_email'], now(), $row['user_id']]);
                } else {
                    db()->exec('UPDATE {users} SET email_verified_at = ? WHERE id = ?', [now(), $row['user_id']]);
                }
                Tokens::burn((int) $row['id']);
                $st = (string) db()->val('SELECT status FROM {users} WHERE id = ?', [$row['user_id']]);
                flash('success', $st === 'pending' ? t('E-mail confirmed. An administrator still has to approve your account.') : t('E-mail confirmed – you can log in now.'));
                redirect('/login');
            }
        }
        http_response_code(410);
        return View::page(t('Link expired'), render('message', ['title' => t('Link expired'), 'text' => t('This confirmation link is invalid or has expired. Request a new one on the login page.')]), ['layout' => 'none']);
    }

    public function resendForm(): string
    {
        return View::page(t('Resend confirmation'), render('auth/resend'), ['class' => 'page-auth']);
    }

    public function resend(): never
    {
        $ident = (string) input('login');
        if (!self::throttled('resend')) {
            $u = db()->one('SELECT id, username, email FROM {users} WHERE (username = ? OR email = ?) AND email_verified_at IS NULL AND status <> \'banned\' LIMIT 1', [$ident, $ident]);
            if ($u && setting('auth.email_verify')) {
                self::sendVerification((int) $u['id'], $u['username'], $u['email']);
            }
            flash('info', t('If that account still needs confirmation, a new link is on its way.'));
        } else {
            flash('danger', t('Too many requests. Please wait a few minutes.'));
        }
        redirect('/login');
    }

    // ------------------------------------------------------------------ password reset
    public function forgotForm(): string
    {
        return View::page(t('Forgot password'), render('auth/forgot'), ['class' => 'page-auth']);
    }

    public function forgot(): never
    {
        if (self::throttled('forgot')) {
            flash('danger', t('Too many requests. Please wait a few minutes.'));
            redirect('/forgot');
        }
        $ident = (string) input('login');
        $u = $ident !== '' ? db()->one("SELECT id, username, email FROM {users} WHERE (username = ? OR email = ?) AND status IN ('active','pending') LIMIT 1", [$ident, $ident]) : null;
        if ($u) { // identical response either way → no account enumeration
            $token = Tokens::create((int) $u['id'], 'reset', 3600);
            $html = Mailer::template(t('Reset your password'), '<p>' . e(t('Hi {name}, we received a request to reset your password. The link is valid for 60 minutes and works once. If this was not you, simply ignore this e-mail.', ['name' => $u['username']])) . '</p>', [t('Choose a new password'), abs_url('reset/' . $token)]);
            Mailer::send($u['email'], t('Reset your password'), $html);
        }
        flash('info', t('If an account matches, we have sent password reset instructions to its e-mail address.'));
        redirect('/login');
    }

    public function resetForm(array $p): string
    {
        if (!Tokens::peek($p['token'], 'reset')) {
            http_response_code(410);
            return View::page(t('Link expired'), render('message', ['title' => t('Link expired'), 'text' => t('This password reset link is invalid or has expired. Please request a new one.')]), ['layout' => 'none']);
        }
        return View::page(t('Choose a new password'), render('auth/reset', ['token' => $p['token']]), ['class' => 'page-auth']);
    }

    public function reset(array $p): string
    {
        $row = Tokens::peek($p['token'], 'reset');
        if (!$row) {
            http_response_code(410);
            return View::page(t('Link expired'), render('message', ['title' => t('Link expired'), 'text' => t('This password reset link is invalid or has expired. Please request a new one.')]), ['layout' => 'none']);
        }
        $pw = (string) ($_POST['password'] ?? '');
        if (strlen($pw) < 8 || strlen($pw) > 72 || $pw !== (string) ($_POST['password2'] ?? '')) {
            http_response_code(422);
            flash('danger', strlen($pw) < 8 || strlen($pw) > 72 ? t('Password must be 8–72 characters.') : t('The passwords do not match.'));
            return View::page(t('Choose a new password'), render('auth/reset', ['token' => $p['token']]), ['class' => 'page-auth']);
        }
        db()->exec('UPDATE {users} SET password_hash = ?, email_verified_at = COALESCE(email_verified_at, ?) WHERE id = ?', [Auth::hash($pw), now(), $row['user_id']]);
        db()->exec('DELETE FROM {remember_tokens} WHERE user_id = ?', [$row['user_id']]); // sign out other devices
        db()->exec("DELETE FROM {email_tokens} WHERE user_id = ? AND type = 'reset'", [$row['user_id']]);
        db()->exec('DELETE FROM {login_attempts} WHERE login = (SELECT LOWER(username) FROM {users} WHERE id = ?)', [$row['user_id']]);
        audit('auth.password_reset', 'user #' . $row['user_id']);
        flash('success', t('Your password has been changed. You can log in now.'));
        redirect('/login');
    }
}
