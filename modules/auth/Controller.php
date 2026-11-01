<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Auth;

use MagicCMS\Core\{Auth, View};

final class Controller
{
    public function loginForm(): string
    {
        if (user()) {
            redirect('/');
        }
        return View::page(t('Login'), render('auth/login', ['next' => safe_next(input('next'), ''), 'old' => '']), ['class' => 'page-auth']);
    }

    public function login(): string
    {
        $login = (string) input('login');
        $next = safe_next(input('next'), '/');
        if ($err = Auth::attempt($login, (string) ($_POST['password'] ?? ''), !empty($_POST['remember']))) {
            http_response_code(401);
            flash('danger', $err);
            return View::page(t('Login'), render('auth/login', ['next' => $next === '/' ? '' : $next, 'old' => $login]), ['class' => 'page-auth']);
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
}
