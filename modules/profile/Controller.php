<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Profile;

use MagicCMS\Core\{Auth, Uploads, View};

final class Controller
{
    public function form(): string
    {
        return View::page(t('My profile'), render('profile/form', ['u' => user()]));
    }

    public function save(): void
    {
        $u = user();
        $bio = mb_substr((string) input('bio'), 0, 500);
        $sig = mb_substr((string) input('signature'), 0, 300);
        $email = (string) input('email');
        $tz = (string) input('timezone');
        $mode = (string) input('theme_mode');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            flash('danger', t('Please enter a valid email address.'));
            redirect('/profile');
        }
        if (db()->val('SELECT id FROM {users} WHERE email = ? AND id <> ?', [$email, $u['id']])) {
            flash('danger', t('That email is already in use.'));
            redirect('/profile');
        }
        $tz = in_array($tz, \DateTimeZone::listIdentifiers(), true) ? $tz : null;
        $mode = in_array($mode, ['dark', 'light', 'auto'], true) ? $mode : null;
        $newEmail = null;
        if (strcasecmp($email, (string) $u['email']) !== 0) {
            if (setting('auth.email_verify')) { // keep the old address until the new one is confirmed
                $newEmail = $email;
                $email = (string) $u['email'];
            }
        }
        db()->exec(
            'UPDATE {users} SET email = ?, bio = ?, signature = ?, timezone = ?, theme_mode = ?, notify_pm = ? WHERE id = ?',
            [$email, $bio, $sig, $tz, $mode, empty($_POST['notify_pm']) ? 0 : 1, $u['id']]
        );
        if ($newEmail !== null) {
            \MagicCMS\Modules\Auth\Controller::sendVerification((int) $u['id'], (string) $u['username'], (string) $u['email'], 'email_change', $newEmail);
            flash('info', t('We sent a confirmation link to {email}. Your address changes once you click it.', ['email' => $newEmail]));
            redirect('/profile');
        }
        flash('success', t('Profile saved.'));
        redirect('/profile');
    }

    public function avatar(): void
    {
        $u = user();
        try {
            $name = Uploads::avatar($_FILES['avatar'] ?? [], (int) $u['id']);
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect('/profile');
        }
        Uploads::deleteAvatar($u['avatar']);
        db()->exec('UPDATE {users} SET avatar = ? WHERE id = ?', [$name, $u['id']]);
        flash('success', t('Avatar updated.'));
        redirect('/profile');
    }

    public function avatarDelete(): void
    {
        $u = user();
        Uploads::deleteAvatar($u['avatar']);
        db()->exec('UPDATE {users} SET avatar = NULL WHERE id = ?', [$u['id']]);
        flash('success', t('Avatar removed.'));
        redirect('/profile');
    }

    public function password(): void
    {
        $u = user();
        $cur = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['password'] ?? '');
        $hash = (string) db()->val('SELECT password_hash FROM {users} WHERE id = ?', [$u['id']]);
        if (!password_verify($cur, $hash)) {
            flash('danger', t('Current password is wrong.'));
        } elseif (strlen($new) < 8 || strlen($new) > 72) {
            flash('danger', t('Password must be 8–72 characters.'));
        } elseif ($new !== (string) ($_POST['password2'] ?? '')) {
            flash('danger', t('The passwords do not match.'));
        } else {
            db()->exec('UPDATE {users} SET password_hash = ? WHERE id = ?', [Auth::hash($new), $u['id']]);
            db()->exec('DELETE FROM {remember_tokens} WHERE user_id = ?', [$u['id']]); // log out other devices
            session_regenerate_id(true);
            flash('success', t('Password changed.'));
        }
        redirect('/profile');
    }
}
