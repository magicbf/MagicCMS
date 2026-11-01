<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Recruit;

use MagicCMS\Core\{Router, View};
use MagicCMS\Modules\Notifications\Notifications;

final class Controller
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('recruit/' . $view, $data), ['class' => 'page-recruit']);
    }

    public function form(): string
    {
        $u = user();
        if (!Recruit::enabled()) {
            return $this->page(t('Join us'), 'closed');
        }
        if (!$u && setting('recruit.require_login')) {
            return $this->page(t('Join us'), 'login');
        }
        $existing = $u ? Recruit::activeFor((int) $u['id'], (string) $u['email']) : null;
        return $this->page(t('Join us'), 'form', [
            'positions' => db()->all('SELECT * FROM {recruit_positions} WHERE is_open = 1 ORDER BY sort_order, id'),
            'questions' => Recruit::questions(), 'existing' => $existing, 'v' => $_SESSION['recruit_old'] ?? [],
        ]);
    }

    public function submit(): never
    {
        $u = user();
        if (!Recruit::enabled() || (!$u && setting('recruit.require_login'))) {
            redirect('/recruit');
        }
        $_SESSION['recruit_old'] = $_POST;
        $fail = static function (string $m): never {
            flash('danger', $m);
            redirect('/recruit');
        };
        if (!empty($_POST['website'])) { // honeypot: pretend success
            unset($_SESSION['recruit_old']);
            flash('success', t('Thank you! Your application has been sent.'));
            redirect('/recruit');
        }
        $name = mb_substr((string) input('name'), 0, 60);
        $email = mb_strtolower(mb_substr((string) input('email'), 0, 190));
        if (mb_strlen($name) < 2) {
            $fail(t('Please enter your name or nickname.'));
        }
        if (!valid_email($email)) {
            $fail(t('Please enter a valid e-mail address.'));
        }
        $pos = db()->one('SELECT * FROM {recruit_positions} WHERE id = ? AND is_open = 1', [(int) input('position')]);
        if (!$pos && db()->val('SELECT 1 FROM {recruit_positions} WHERE is_open = 1')) {
            $fail(t('Please choose a position.'));
        }
        $answers = [];
        foreach (Recruit::questions() as $q) {
            $raw = $_POST['q'][$q['id']] ?? '';
            $a = is_string($raw) ? trim($raw) : '';
            $a = mb_substr($a, 0, $q['qtype'] === 'textarea' ? 2000 : 300);
            if ($q['qtype'] === 'select' && $a !== '' && !in_array($a, Recruit::options($q), true)) {
                $a = '';
            }
            if ($q['qtype'] === 'yesno' && !in_array($a, ['yes', 'no'], true)) {
                $a = '';
            }
            if ($a === '' && $q['required']) {
                $fail(t('Please answer: {q}', ['q' => $q['label']]));
            }
            $answers[] = ['q' => $q['label'], 'type' => $q['qtype'], 'a' => $a];
        }
        if (Recruit::activeFor($u ? (int) $u['id'] : null, $email)) {
            $fail(t('You already have an open application. You can follow it with the link we e-mailed you, or withdraw it first.'));
        }
        $since = gmdate('Y-m-d H:i:s', time() - 3600);
        if ((int) db()->val('SELECT COUNT(*) FROM {recruit_applications} WHERE ip_hash = ? AND created_at > ?', [ip_hash(), $since]) >= 3) {
            $fail(t('Too many applications from your connection. Please try again later.'));
        }
        $token = bin2hex(random_bytes(16));
        $id = db()->insert(
            'INSERT INTO {recruit_applications} (user_id, position_id, position_title, name, email, answers, status, token, ip_hash, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$u['id'] ?? null, $pos['id'] ?? null, $pos['title'] ?? '', $name, $email, json_encode($answers, JSON_UNESCAPED_UNICODE), 'new', $token, ip_hash(), now(), now()]
        );
        unset($_SESSION['recruit_old']);
        Notifications::addForPermission('admin.recruit', 'recruit_new', t('New application: {name}', ['name' => $name]), '/admin/recruit/' . $id, ($pos['title'] ?? '') . ' · ' . $email, 'recruit:' . $id);
        Recruit::mail($email, t('We received your application'), t('Thanks, {name}!', ['name' => $name]),
            t('Your application to {site} has been received. You can follow its status and withdraw it at any time with the link below. Keep it private – anyone with the link can see your application.', ['site' => (string) setting('site.name')]),
            [t('Check your application status'), Recruit::link($token)]);
        foreach (array_filter(array_map('trim', explode(',', (string) setting('recruit.notify_emails', '')))) as $to) {
            Recruit::mail($to, t('New application: {name}', ['name' => $name]), t('New application'), $name . ' · ' . ($pos['title'] ?? '') . ' · ' . $email, [t('Review it'), abs_url('admin/recruit/' . $id)]);
        }
        Recruit::housekeeping();
        flash('success', t('Thank you! Your application has been sent.'));
        redirect('/recruit/status/' . $token);
    }

    private function load(string $token): ?array
    {
        return preg_match('/^[a-f0-9]{32}$/', $token) ? db()->one('SELECT * FROM {recruit_applications} WHERE token = ?', [$token]) : null;
    }

    public function status(array $p): string
    {
        $a = $this->load($p['token']);
        if (!$a) {
            (new Router())->notFound();
            return '';
        }
        header('X-Robots-Tag: noindex');
        return $this->page(t('Your application'), 'status', ['a' => $a, 'answers' => json_decode((string) $a['answers'], true) ?: []]);
    }

    public function withdraw(array $p): never
    {
        $a = $this->load($p['token']);
        if ($a && in_array($a['status'], Recruit::ACTIVE, true)) {
            db()->exec("UPDATE {recruit_applications} SET status = 'withdrawn', updated_at = ? WHERE id = ?", [now(), $a['id']]);
            Notifications::addForPermission('admin.recruit', 'recruit_status', t('{name} withdrew their application', ['name' => $a['name']]), '/admin/recruit/' . $a['id'], '', 'recruit:' . $a['id']);
            flash('success', t('Your application has been withdrawn.'));
        }
        redirect('/recruit/status/' . $p['token']);
    }

    public function mine(): string
    {
        $rows = db()->all('SELECT * FROM {recruit_applications} WHERE user_id = ? ORDER BY id DESC', [(int) user()['id']]);
        return $this->page(t('My applications'), 'mine', ['rows' => $rows]);
    }
}
