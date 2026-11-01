<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Recruit;

use MagicCMS\Core\{Router, Settings, View};
use MagicCMS\Modules\Notifications\Notifications;

final class AdminController
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'recruit', 'title' => $title, 'inner' => render('recruit/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function index(): string
    {
        $f = array_key_exists((string) input('f'), Recruit::STATUSES) ? (string) input('f') : '';
        $q = mb_substr((string) input('q', ''), 0, 60);
        $where = ['1=1'];
        $args = [];
        if ($f !== '') {
            $where[] = 'status = ?';
            $args[] = $f;
        }
        if ($q !== '') {
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $args[] = $args[] = '%' . addcslashes($q, '%_\\') . '%';
        }
        $rows = db()->all('SELECT * FROM {recruit_applications} WHERE ' . implode(' AND ', $where) . ' ORDER BY FIELD(status,\'new\',\'review\',\'interview\',\'accepted\',\'declined\',\'withdrawn\'), id DESC LIMIT 200', $args);
        $counts = array_column(db()->all('SELECT status, COUNT(*) n FROM {recruit_applications} GROUP BY status'), 'n', 'status');
        return $this->page(t('Recruitment'), 'admin_list', ['rows' => $rows, 'counts' => $counts, 'f' => $f, 'q' => $q]);
    }

    public function show(array $p): string
    {
        $a = db()->one('SELECT * FROM {recruit_applications} WHERE id = ?', [(int) $p['id']]);
        if (!$a) {
            redirect('/admin/recruit');
        }
        $notes = db()->all('SELECT n.*, u.username FROM {recruit_notes} n LEFT JOIN {users} u ON u.id = n.user_id WHERE n.application_id = ? ORDER BY n.id', [$a['id']]);
        $account = $a['user_id'] ? db()->one('SELECT id, username FROM {users} WHERE id = ?', [$a['user_id']]) : null;
        $dupes = (int) db()->val('SELECT COUNT(*) FROM {recruit_applications} WHERE id <> ? AND (email = ? OR (user_id IS NOT NULL AND user_id = ?))', [$a['id'], $a['email'], $a['user_id']]);
        Notifications::markReadByRef((int) user()['id'], 'recruit:' . $a['id']);
        return $this->page($a['name'], 'admin_show', ['a' => $a, 'answers' => json_decode((string) $a['answers'], true) ?: [], 'notes' => $notes, 'account' => $account, 'dupes' => $dupes]);
    }

    public function setStatus(array $p): never
    {
        $a = db()->one('SELECT * FROM {recruit_applications} WHERE id = ?', [(int) $p['id']]);
        $st = (string) input('status');
        if ($a && isset(Recruit::STATUSES[$st]) && $st !== 'withdrawn') {
            $msg = mb_substr((string) input('message'), 0, 500);
            db()->exec('UPDATE {recruit_applications} SET status = ?, status_message = ?, updated_at = ? WHERE id = ?', [$st, $msg, now(), $a['id']]);
            audit('recruit.status', '#' . $a['id'] . ' → ' . $st);
            if ($st !== $a['status'] || $msg !== $a['status_message']) {
                $title = t('Your application: {status}', ['status' => Recruit::label($st)]);
                if ($a['user_id']) {
                    notify_user((int) $a['user_id'], 'recruit_status', $title, '/recruit/status/' . $a['token'], $msg, 'recruit-status:' . $a['id']);
                }
                if (input('notify') === '1') {
                    Recruit::mail($a['email'], $title, $title, $msg !== '' ? $msg : t('The status of your application changed.'), [t('View your application'), Recruit::link($a['token'])]);
                }
            }
            flash('success', t('Status updated.'));
        }
        redirect('/admin/recruit/' . (int) $p['id']);
    }

    public function note(array $p): never
    {
        $body = mb_substr((string) input('body'), 0, 2000);
        if ($body !== '' && db()->val('SELECT 1 FROM {recruit_applications} WHERE id = ?', [(int) $p['id']])) {
            db()->exec('INSERT INTO {recruit_notes} (application_id, user_id, body, created_at) VALUES (?,?,?,?)', [(int) $p['id'], user()['id'], $body, now()]);
        }
        redirect('/admin/recruit/' . (int) $p['id']);
    }

    public function delete(array $p): never
    {
        db()->exec('DELETE FROM {recruit_notes} WHERE application_id = ?', [(int) $p['id']]);
        db()->exec('DELETE FROM {recruit_applications} WHERE id = ?', [(int) $p['id']]);
        audit('recruit.delete', '#' . (int) $p['id']);
        flash('success', t('Application deleted.'));
        redirect('/admin/recruit');
    }

    // ------------------------------------------------------------ setup
    public function setup(): string
    {
        return $this->page(t('Recruitment setup'), 'admin_setup', [
            'positions' => db()->all('SELECT * FROM {recruit_positions} ORDER BY sort_order, id'),
            'questions' => db()->all('SELECT * FROM {recruit_questions} ORDER BY sort_order, id'),
        ]);
    }

    public function saveSettings(): never
    {
        Settings::set('recruit.enabled', input('enabled') === '1');
        Settings::set('recruit.require_login', input('require_login') === '1');
        Settings::set('recruit.intro', mb_substr((string) input('intro'), 0, 1000));
        $mails = array_filter(array_map('trim', explode(',', (string) input('notify_emails'))), static fn($m) => valid_email($m));
        Settings::set('recruit.notify_emails', implode(', ', array_slice($mails, 0, 5)));
        Settings::set('recruit.retention_days', max(0, min(3650, (int) input('retention_days', 180))));
        flash('success', t('Settings saved.'));
        redirect('/admin/recruit/setup');
    }

    public function savePosition(): never
    {
        $id = (int) input('id', 0);
        $title = mb_substr((string) input('title'), 0, 80);
        if ($title !== '') {
            $d = mb_substr((string) input('description'), 0, 300);
            $open = input('is_open') === '1' ? 1 : 0;
            $ord = (int) input('sort_order', 0);
            $id ? db()->exec('UPDATE {recruit_positions} SET title = ?, description = ?, is_open = ?, sort_order = ? WHERE id = ?', [$title, $d, $open, $ord, $id])
                : db()->exec('INSERT INTO {recruit_positions} (title, description, is_open, sort_order) VALUES (?,?,?,?)', [$title, $d, $open, $ord ?: 99]);
            flash('success', t('Position saved.'));
        }
        redirect('/admin/recruit/setup#positions');
    }

    public function deletePosition(array $p): never
    {
        db()->exec('DELETE FROM {recruit_positions} WHERE id = ?', [(int) $p['id']]);
        redirect('/admin/recruit/setup#positions');
    }

    public function saveQuestion(): never
    {
        $id = (int) input('id', 0);
        $label = mb_substr((string) input('label'), 0, 200);
        $type = isset(Recruit::TYPES[(string) input('qtype')]) ? (string) input('qtype') : 'text';
        if ($label !== '') {
            $opts = $type === 'select' ? mb_substr(implode('|', array_filter(array_map('trim', preg_split('/[|\n]/', (string) input('options')) ?: []), 'strlen')), 0, 500) : '';
            if ($type === 'select' && $opts === '') {
                $type = 'text';
            }
            $f = [$label, mb_substr((string) input('help'), 0, 200), $type, $opts, input('required') === '1' ? 1 : 0, input('enabled') === '1' ? 1 : 0, (int) input('sort_order', 0)];
            $id ? db()->exec('UPDATE {recruit_questions} SET label = ?, help = ?, qtype = ?, options = ?, required = ?, enabled = ?, sort_order = ? WHERE id = ?', [...$f, $id])
                : db()->exec('INSERT INTO {recruit_questions} (label, help, qtype, options, required, enabled, sort_order) VALUES (?,?,?,?,?,?,?)', [...array_slice($f, 0, 6), $f[6] ?: 99]);
            flash('success', t('Question saved.'));
        }
        redirect('/admin/recruit/setup#questions');
    }

    public function deleteQuestion(array $p): never
    {
        db()->exec('DELETE FROM {recruit_questions} WHERE id = ?', [(int) $p['id']]);
        redirect('/admin/recruit/setup#questions');
    }
}
