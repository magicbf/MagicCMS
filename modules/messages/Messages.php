<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Messages;

use MagicCMS\Core\{Mailer};

/** Private messaging helpers: unread counts, blocking, notifications. */
final class Messages
{
    public const MAX_RECIPIENTS = 5;
    public const PER_PAGE = 20;

    public static function unread(int $userId): int
    {
        return (int) db()->val(
            'SELECT COUNT(*) FROM {pm_participants} p JOIN {pm_threads} t ON t.id = p.thread_id
             WHERE p.user_id = ? AND p.hidden = 0 AND EXISTS (SELECT 1 FROM {pm_messages} m WHERE m.thread_id = t.id AND m.id > p.last_read_id AND m.user_id <> ?)',
            [$userId, $userId]
        );
    }

    public static function isBlocked(int $by, int $who): bool
    {
        return (bool) db()->val('SELECT 1 FROM {pm_blocks} WHERE user_id = ? AND blocked_id = ?', [$by, $who]);
    }

    public static function participant(int $threadId, int $userId): ?array
    {
        return db()->one('SELECT * FROM {pm_participants} WHERE thread_id = ? AND user_id = ? AND hidden = 0', [$threadId, $userId]);
    }

    /** Seconds the sender still has to wait (flood protection). */
    public static function floodWait(int $userId): int
    {
        $last = db()->val('SELECT MAX(created_at) FROM {pm_messages} WHERE user_id = ?', [$userId]);
        return $last ? max(0, 5 - (time() - (int) strtotime($last . ' UTC'))) : 0;
    }

    /** Add a message, bump the thread, un-hide it for the other participants. */
    public static function add(int $threadId, int $userId, string $body): int
    {
        $id = db()->insert('INSERT INTO {pm_messages} (thread_id, user_id, body, created_at) VALUES (?,?,?,?)', [$threadId, $userId, $body, now()]);
        db()->exec('UPDATE {pm_threads} SET last_message_id = ?, updated_at = ? WHERE id = ?', [$id, now(), $threadId]);
        db()->exec('UPDATE {pm_participants} SET hidden = 0 WHERE thread_id = ? AND user_id <> ?', [$threadId, $userId]);
        db()->exec('UPDATE {pm_participants} SET last_read_id = ? WHERE thread_id = ? AND user_id = ?', [$id, $threadId, $userId]);
        return $id;
    }

    /**
     * E-mail the other participants (opt-out in their profile). At most one mail per unread streak,
     * and the mail never contains the message text – only who wrote and where to read it.
     */
    public static function notify(int $threadId, int $senderId, string $subject): void
    {
        $sender = (string) db()->val('SELECT username FROM {users} WHERE id = ?', [$senderId]);
        $rows = db()->all(
            "SELECT p.user_id, p.notified_at, u.username, u.email FROM {pm_participants} p JOIN {users} u ON u.id = p.user_id
             WHERE p.thread_id = ? AND p.user_id <> ? AND u.notify_pm = 1 AND u.status = 'active' AND (u.email_verified_at IS NOT NULL OR ? = 0)",
            [$threadId, $senderId, (int) (bool) setting('auth.email_verify')]
        );
        foreach ($rows as $r) {
            if ($r['notified_at'] !== null) {
                continue; // already notified and has not read since
            }
            $html = Mailer::template(
                t('New private message'),
                '<p>' . e(t('Hi {name}, {sender} sent you a private message: “{subject}”.', ['name' => $r['username'], 'sender' => $sender, 'subject' => $subject])) . '</p>',
                [t('Read the message'), abs_url('messages/' . $threadId)],
                t('You can switch these notifications off in your profile settings.')
            );
            if (Mailer::send($r['email'], t('New private message from {sender}', ['sender' => $sender]), $html)) {
                db()->exec('UPDATE {pm_participants} SET notified_at = ? WHERE thread_id = ? AND user_id = ?', [now(), $threadId, $r['user_id']]);
            }
        }
    }
}
