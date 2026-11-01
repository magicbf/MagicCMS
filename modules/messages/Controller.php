<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Messages;

use MagicCMS\Core\{Router, View};

final class Controller
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('messages/' . $view, $data), ['class' => 'page-messages', 'layout' => 'none']);
    }

    private function nf(): string
    {
        (new Router())->notFound();
        return '';
    }

    public function inbox(): string
    {
        $uid = (int) user()['id'];
        $total = (int) db()->val('SELECT COUNT(*) FROM {pm_participants} WHERE user_id = ? AND hidden = 0', [$uid]);
        $pages = max(1, (int) ceil($total / Messages::PER_PAGE));
        $page = max(1, min($pages, (int) input('page', 1)));
        $threads = db()->all(
            'SELECT t.id, t.subject, t.updated_at, m.body AS last_body, m.user_id AS last_user, lu.username AS last_name,
                    (SELECT COUNT(*) FROM {pm_messages} x WHERE x.thread_id = t.id AND x.id > p.last_read_id AND x.user_id <> ?) AS unread
             FROM {pm_participants} p JOIN {pm_threads} t ON t.id = p.thread_id
             JOIN {pm_messages} m ON m.id = t.last_message_id JOIN {users} lu ON lu.id = m.user_id
             WHERE p.user_id = ? AND p.hidden = 0 ORDER BY t.updated_at DESC, t.id DESC LIMIT ' . Messages::PER_PAGE . ' OFFSET ' . (($page - 1) * Messages::PER_PAGE),
            [$uid, $uid]
        );
        foreach ($threads as &$t) {
            $t['people'] = db()->all('SELECT u.id, u.username, u.avatar FROM {pm_participants} p JOIN {users} u ON u.id = p.user_id WHERE p.thread_id = ? AND p.user_id <> ? ORDER BY u.username', [$t['id'], $uid]);
        }
        $blocked = db()->all('SELECT u.id, u.username FROM {pm_blocks} b JOIN {users} u ON u.id = b.blocked_id WHERE b.user_id = ? ORDER BY u.username', [$uid]);
        return $this->page(t('Messages'), 'index', ['threads' => $threads, 'blocked' => $blocked, 'page' => $page, 'pages' => $pages]);
    }

    public function compose(): string
    {
        return $this->page(t('New message'), 'compose', ['to' => (string) input('to', ''), 'subject' => '', 'body' => '']);
    }

    /** @return array{0:array,1:?string} recipient rows and an error message */
    private function recipients(string $csv): array
    {
        $me = (int) user()['id'];
        $names = array_values(array_unique(array_filter(array_map('trim', explode(',', $csv)), fn($n) => $n !== '')));
        if (!$names) {
            return [[], t('Please enter at least one recipient.')];
        }
        if (count($names) > Messages::MAX_RECIPIENTS) {
            return [[], t('You can write to at most {n} people at once.', ['n' => Messages::MAX_RECIPIENTS])];
        }
        $rows = [];
        foreach ($names as $n) {
            $u = db()->one("SELECT id, username FROM {users} WHERE username = ? AND status = 'active'", [$n]);
            if (!$u) {
                return [[], t('User “{name}” was not found.', ['name' => $n])];
            }
            if ((int) $u['id'] === $me) {
                return [[], t('You cannot send a message to yourself.')];
            }
            if (Messages::isBlocked((int) $u['id'], $me)) {
                return [[], t('{name} does not accept messages from you.', ['name' => $u['username']])];
            }
            $rows[] = $u;
        }
        return [$rows, null];
    }

    private function validate(string $subject, string $body, bool $needSubject): ?string
    {
        if ($needSubject && (mb_strlen($subject) < 2 || mb_strlen($subject) > 140)) {
            return t('The subject must be 2–140 characters.');
        }
        if (mb_strlen(trim($body)) < 1 || mb_strlen($body) > 5000) {
            return t('The message must be 1–5,000 characters.');
        }
        if (($w = Messages::floodWait((int) user()['id'])) > 0) {
            return t('Please wait {n} seconds before sending another message.', ['n' => $w]);
        }
        return null;
    }

    public function create(): string
    {
        $me = (int) user()['id'];
        $to = (string) input('to');
        $subject = (string) input('subject');
        $body = (string) ($_POST['body'] ?? '');
        [$rcpt, $err] = $this->recipients($to);
        $err ??= $this->validate($subject, $body, true);
        if (!$err && (int) db()->val('SELECT COUNT(*) FROM {pm_threads} WHERE created_by = ? AND created_at > ?', [$me, gmdate('Y-m-d H:i:s', time() - 3600)]) >= 20) {
            $err = t('You have started too many conversations. Please try again later.');
        }
        if ($err) {
            http_response_code(422);
            flash('danger', $err);
            return $this->page(t('New message'), 'compose', ['to' => $to, 'subject' => $subject, 'body' => $body]);
        }
        $tid = db()->insert('INSERT INTO {pm_threads} (subject, created_by, updated_at, created_at) VALUES (?,?,?,?)', [$subject, $me, now(), now()]);
        db()->exec('INSERT INTO {pm_participants} (thread_id, user_id) VALUES (?,?)', [$tid, $me]);
        foreach ($rcpt as $u) {
            db()->exec('INSERT INTO {pm_participants} (thread_id, user_id) VALUES (?,?)', [$tid, $u['id']]);
        }
        Messages::add($tid, $me, $body);
        Messages::notify($tid, $me, $subject);
        flash('success', t('Message sent.'));
        redirect('/messages/' . $tid);
    }

    public function thread(array $p): string
    {
        $me = (int) user()['id'];
        $tid = (int) $p['id'];
        $part = Messages::participant($tid, $me);
        $t = $part ? db()->one('SELECT * FROM {pm_threads} WHERE id = ?', [$tid]) : null;
        if (!$t) {
            return $this->nf();
        }
        $msgs = db()->all(
            'SELECT m.*, u.username, u.avatar, r.slug AS role_slug FROM {pm_messages} m JOIN {users} u ON u.id = m.user_id JOIN {roles} r ON r.id = u.role_id WHERE m.thread_id = ? ORDER BY m.id LIMIT 300',
            [$tid]
        );
        db()->exec('UPDATE {pm_participants} SET last_read_id = ?, notified_at = NULL WHERE thread_id = ? AND user_id = ?', [$t['last_message_id'], $tid, $me]);
        $people = db()->all('SELECT u.id, u.username, u.avatar FROM {pm_participants} p JOIN {users} u ON u.id = p.user_id WHERE p.thread_id = ? ORDER BY u.username', [$tid]);
        $lastRead = (int) $part['last_read_id'];
        return $this->page($t['subject'], 'thread', ['t' => $t, 'msgs' => $msgs, 'people' => $people, 'lastRead' => $lastRead, 'blockedIds' => array_column(db()->all('SELECT blocked_id FROM {pm_blocks} WHERE user_id = ?', [$me]), 'blocked_id')]);
    }

    public function reply(array $p): never
    {
        $me = (int) user()['id'];
        $tid = (int) $p['id'];
        $t = Messages::participant($tid, $me) ? db()->one('SELECT * FROM {pm_threads} WHERE id = ?', [$tid]) : null;
        if (!$t) {
            redirect('/messages');
        }
        $body = (string) ($_POST['body'] ?? '');
        $others = db()->all('SELECT user_id FROM {pm_participants} WHERE thread_id = ? AND user_id <> ?', [$tid, $me]);
        $allBlock = $others && !array_filter($others, fn($o) => !Messages::isBlocked((int) $o['user_id'], $me));
        if ($allBlock) {
            flash('danger', t('The other participant does not accept messages from you.'));
        } elseif ($err = $this->validate('', $body, false)) {
            flash('danger', $err);
        } else {
            Messages::add($tid, $me, $body);
            Messages::notify($tid, $me, (string) $t['subject']);
        }
        redirect('/messages/' . $tid . '#end');
    }

    public function delete(array $p): never
    {
        $me = (int) user()['id'];
        $tid = (int) $p['id'];
        if (Messages::participant($tid, $me)) {
            db()->exec('UPDATE {pm_participants} SET hidden = 1 WHERE thread_id = ? AND user_id = ?', [$tid, $me]);
            if (!db()->val('SELECT 1 FROM {pm_participants} WHERE thread_id = ? AND hidden = 0', [$tid])) { // everyone left → purge
                db()->exec('DELETE FROM {pm_messages} WHERE thread_id = ?', [$tid]);
                db()->exec('DELETE FROM {pm_participants} WHERE thread_id = ?', [$tid]);
                db()->exec('DELETE FROM {pm_threads} WHERE id = ?', [$tid]);
            }
            flash('success', t('Conversation deleted.'));
        }
        redirect('/messages');
    }

    public function block(array $p): never
    {
        $uid = (int) $p['uid'];
        $me = (int) user()['id'];
        if ($uid !== $me && db()->val('SELECT id FROM {users} WHERE id = ?', [$uid])) {
            db()->exec('INSERT IGNORE INTO {pm_blocks} (user_id, blocked_id, created_at) VALUES (?,?,?)', [$me, $uid, now()]);
            flash('success', t('User blocked. They can no longer message you.'));
        }
        redirect('/messages');
    }

    public function unblock(array $p): never
    {
        db()->exec('DELETE FROM {pm_blocks} WHERE user_id = ? AND blocked_id = ?', [user()['id'], (int) $p['uid']]);
        flash('success', t('User unblocked.'));
        redirect('/messages');
    }
}
