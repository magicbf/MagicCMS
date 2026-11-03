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
                SELECT m.id, m.message, m.created_at, u.id AS uid, u.username, u.avatar, r.level AS lvl, r.slug AS role, r.name AS role_name
                FROM {chat_messages} m JOIN {users} u ON u.id = m.user_id JOIN {roles} r ON r.id = u.role_id
                WHERE m.id > ? ORDER BY m.id DESC LIMIT 40) t ORDER BY id ASC',
            [$after]
        );
        $myLevel = \MagicCMS\Core\Auth::level();
        $meId = user() ? (int) user()['id'] : 0;
        $today = fmt_date(now(), 'Y-m-d');
        $yesterday = fmt_date(gmdate('Y-m-d H:i:s', time() - 86400), 'Y-m-d');
        foreach ($rows as &$r) {
            $day = fmt_date($r['created_at'], 'Y-m-d');
            $r['day'] = $day;
            $r['day_label'] = $day === $today ? t('Today') : ($day === $yesterday ? t('Yesterday') : fmt_date($r['created_at'], 'M j'));
            $r['ts'] = (int) strtotime($r['created_at'] . ' UTC');
            $r['role_short'] = ['superadmin' => t('Owner'), 'admin' => t('Admin'), 'moderator' => t('Mod')][$r['role']] ?? '';
            $r['me'] = $meId && $meId === (int) $r['uid'];
            $r['can_delete'] = can('chat.moderate') && ($myLevel >= (int) $r['lvl'] || (user() && (int) user()['id'] === (int) $r['uid']));
            unset($r['lvl']);
            $r['avatar_url'] = avatar_url(['id' => $r['uid'], 'avatar' => $r['avatar']], 32);
            $r['time'] = fmt_date($r['created_at'], 'H:i');
            $r['profile'] = url('user/' . $r['username']);
            unset($r['avatar'], $r['created_at']);
        }
        $meta = [
            'chatters' => (int) db()->val('SELECT COUNT(DISTINCT user_id) FROM {chat_messages} WHERE created_at > ?', [gmdate('Y-m-d H:i:s', time() - 900)]),
            'today' => (int) db()->val('SELECT COUNT(*) FROM {chat_messages} WHERE created_at > ?', [gmdate('Y-m-d H:i:s', time() - 86400)]),
        ];
        json_out(['ok' => true, 'messages' => $rows, 'can_delete' => can('chat.moderate'), 'meta' => $meta]);
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
        $m = db()->one('SELECT user_id FROM {chat_messages} WHERE id = ?', [(int) $p['id']]);
        if ($m && !\MagicCMS\Core\Auth::mayAffect((int) $m['user_id'])) {
            json_out(['ok' => false, 'error' => 'rank'], 403);
        }
        db()->exec('DELETE FROM {chat_messages} WHERE id = ?', [(int) $p['id']]);
        audit('chat.delete', 'message #' . (int) $p['id']);
        json_out(['ok' => true]);
    }
}
