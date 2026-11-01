<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Chat;

final class Controller
{
    public function messages(): void
    {
        $after = max(0, (int) input('after', 0));
        $rows = db()->all(
            'SELECT * FROM (
                SELECT m.id, m.message, m.created_at, u.id AS uid, u.username, u.avatar, r.slug AS role
                FROM {chat_messages} m JOIN {users} u ON u.id = m.user_id JOIN {roles} r ON r.id = u.role_id
                WHERE m.id > ? ORDER BY m.id DESC LIMIT 40) t ORDER BY id ASC',
            [$after]
        );
        foreach ($rows as &$r) {
            $r['avatar_url'] = avatar_url(['id' => $r['uid'], 'avatar' => $r['avatar']], 32);
            $r['time'] = fmt_date($r['created_at'], 'H:i');
            $r['profile'] = url('user/' . $r['username']);
            unset($r['avatar'], $r['created_at']);
        }
        json_out(['ok' => true, 'messages' => $rows, 'can_delete' => can('chat.moderate')]);
    }

    public function post(): void
    {
        $msg = trim(preg_replace('/\s+/u', ' ', (string) input('message', '')) ?? '');
        if ($msg === '' || mb_strlen($msg) > 300) {
            json_out(['ok' => false, 'error' => t('Message must be 1–300 characters.')], 422);
        }
        $u = user();
        $last = db()->val('SELECT MAX(created_at) FROM {chat_messages} WHERE user_id = ?', [$u['id']]);
        if ($last && time() - strtotime($last . ' UTC') < 2) {
            json_out(['ok' => false, 'error' => t('Slow down a little.')], 429);
        }
        db()->exec('INSERT INTO {chat_messages} (user_id, message, created_at) VALUES (?,?,?)', [$u['id'], $msg, now()]);
        if (random_int(1, 30) === 1) { // keep the table small
            db()->exec('DELETE FROM {chat_messages} WHERE id < (SELECT m FROM (SELECT MAX(id) - 500 AS m FROM {chat_messages}) x)');
        }
        json_out(['ok' => true]);
    }

    public function delete(array $p): void
    {
        db()->exec('DELETE FROM {chat_messages} WHERE id = ?', [(int) $p['id']]);
        audit('chat.delete', 'message #' . (int) $p['id']);
        json_out(['ok' => true]);
    }
}
