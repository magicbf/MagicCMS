<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Notifications;

/**
 * In-site notifications (bell in the header). Other modules call Notifications::add().
 * A `ref` (e.g. "topic:42") lets repeated events for the same thing merge into one unread entry.
 */
final class Notifications
{
    public const ICONS = ['forum_reply' => '💬', 'forum_topic' => '🆕', 'recruit_new' => '📝', 'recruit_status' => '📋', 'report' => '🛡️', 'generic' => '🔔'];

    public static function add(int $userId, string $type, string $title, string $url = '', string $body = '', string $ref = ''): void
    {
        $url = ($url !== '' && $url[0] === '/' && !str_starts_with($url, '//')) ? $url : '';
        if ($ref !== '') { // merge into the unread notification for the same thing
            $existing = db()->val('SELECT id FROM {notifications} WHERE user_id = ? AND ref = ? AND read_at IS NULL', [$userId, $ref]);
            if ($existing) {
                db()->exec('UPDATE {notifications} SET title = ?, body = ?, url = ?, created_at = ? WHERE id = ?', [mb_substr($title, 0, 160), mb_substr($body, 0, 300), $url, now(), $existing]);
                return;
            }
        }
        db()->exec(
            'INSERT INTO {notifications} (user_id, type, ref, title, body, url, created_at) VALUES (?,?,?,?,?,?,?)',
            [$userId, mb_substr($type, 0, 32), mb_substr($ref, 0, 48), mb_substr($title, 0, 160), mb_substr($body, 0, 300), $url, now()]
        );
    }

    /** Notify everybody who holds a permission (superadmins always count). */
    public static function addForPermission(string $perm, string $type, string $title, string $url = '', string $body = '', string $ref = '', ?int $exceptUserId = null): void
    {
        $ids = db()->all(
            "SELECT DISTINCT u.id FROM {users} u JOIN {roles} r ON r.id = u.role_id
             LEFT JOIN {role_permissions} rp ON rp.role_id = r.id LEFT JOIN {permissions} p ON p.id = rp.permission_id AND p.perm_key = ?
             WHERE u.status = 'active' AND (r.level >= 100 OR p.id IS NOT NULL)",
            [$perm]
        );
        foreach ($ids as $r) {
            if ($exceptUserId === null || (int) $r['id'] !== $exceptUserId) {
                self::add((int) $r['id'], $type, $title, $url, $body, $ref);
            }
        }
    }

    public static function unread(int $userId): int
    {
        return (int) db()->val('SELECT COUNT(*) FROM {notifications} WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }

    public static function markReadByRef(int $userId, string $ref): void
    {
        db()->exec('UPDATE {notifications} SET read_at = ? WHERE user_id = ? AND ref = ? AND read_at IS NULL', [now(), $userId, $ref]);
    }
}
