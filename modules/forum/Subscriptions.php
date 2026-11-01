<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

use MagicCMS\Core\{Bbcode, Bootstrap, Mailer};

/**
 * Topic and subcategory subscriptions: in-site notifications always, e-mail opt-out in the profile.
 * E-mail is sent once per "unread streak" per topic (reset when the member opens the topic) and
 * only to members who can still read the subcategory. Every mail carries a signed one-click unsubscribe link.
 */
final class Subscriptions
{
    private const MAX_RECIPIENTS = 300;

    public static function sig(string $kind, int $uid, int $id): string
    {
        return substr(hash_hmac('sha256', "unsub|$kind|$uid|$id", (string) (Bootstrap::$config['app']['secret'] ?? '')), 0, 32);
    }

    public static function unsubLink(string $kind, int $uid, int $id): string
    {
        return abs_url("forum/unsub/$kind/$id/$uid/" . self::sig($kind, $uid, $id));
    }

    public static function isTopic(int $uid, int $topicId): bool
    {
        return (bool) db()->val('SELECT 1 FROM {forum_topic_subs} WHERE user_id = ? AND topic_id = ?', [$uid, $topicId]);
    }

    public static function isBoard(int $uid, int $boardId): bool
    {
        return (bool) db()->val('SELECT 1 FROM {forum_board_subs} WHERE user_id = ? AND board_id = ?', [$uid, $boardId]);
    }

    public static function setTopic(int $uid, int $topicId, bool $on): void
    {
        $on ? db()->exec('INSERT IGNORE INTO {forum_topic_subs} (user_id, topic_id, created_at) VALUES (?,?,?)', [$uid, $topicId, now()])
            : db()->exec('DELETE FROM {forum_topic_subs} WHERE user_id = ? AND topic_id = ?', [$uid, $topicId]);
        if (!$on) {
            \MagicCMS\Modules\Notifications\Notifications::markReadByRef($uid, 'topic:' . $topicId);
        }
    }

    public static function setBoard(int $uid, int $boardId, bool $on): void
    {
        $on ? db()->exec('INSERT IGNORE INTO {forum_board_subs} (user_id, board_id, created_at) VALUES (?,?,?)', [$uid, $boardId, now()])
            : db()->exec('DELETE FROM {forum_board_subs} WHERE user_id = ? AND board_id = ?', [$uid, $boardId]);
    }

    /** Authors follow their own topics and replies automatically (profile switch). */
    public static function autoSubscribe(int $topicId): void
    {
        if (user() && !empty(user()['forum_autosub'])) {
            self::setTopic((int) user()['id'], $topicId, true);
        }
    }

    /** The member looked at the topic: clear its notification and re-arm e-mail. */
    public static function seen(int $uid, int $topicId): void
    {
        db()->exec('UPDATE {forum_topic_subs} SET notified_at = NULL WHERE user_id = ? AND topic_id = ? AND notified_at IS NOT NULL', [$uid, $topicId]);
        \MagicCMS\Modules\Notifications\Notifications::markReadByRef($uid, 'topic:' . $topicId);
    }

    private static function mailOk(array $r): bool
    {
        return (int) $r['notify_forum'] === 1 && !empty($r['email']) && (!setting('auth.email_verify') || !empty($r['email_verified_at']));
    }

    private static function send(array $r, string $subject, string $headline, string $excerpt, string $button, string $link, string $kind, int $id): void
    {
        $unsub = self::unsubLink($kind, (int) $r['user_id'], $id);
        $html = Mailer::template($headline, '<p>' . e($excerpt) . '</p>', [$button, abs_url(ltrim($link, '/'))],
            t('You get this because you follow this on {site}.', ['site' => (string) setting('site.name')]) . ' ' . t('Unsubscribe: {link}', ['link' => $unsub]));
        $to = (string) $r['email'];
        defer(static fn() => Mailer::send($to, $subject, $html));
    }

    /** New reply → followers of the topic. */
    public static function onReply(array $topic, int $postId, int $authorId, string $body): void
    {
        $author = (string) db()->val('SELECT username FROM {users} WHERE id = ?', [$authorId]);
        $rows = db()->all(
            "SELECT s.user_id, s.notified_at, u.email, u.notify_forum, u.email_verified_at FROM {forum_topic_subs} s
             JOIN {users} u ON u.id = s.user_id JOIN {roles} r ON r.id = u.role_id
             WHERE s.topic_id = ? AND s.user_id <> ? AND u.status = 'active' AND r.level >= ? LIMIT " . self::MAX_RECIPIENTS,
            [$topic['id'], $authorId, (int) $topic['read_level']]
        );
        $excerpt = Bbcode::excerpt($body, 200);
        foreach ($rows as $r) {
            notify_user((int) $r['user_id'], 'forum_reply', t('New reply in “{title}”', ['title' => $topic['title']]), '/forum/post/' . $postId, $author . ': ' . $excerpt, 'topic:' . $topic['id']);
            if ($r['notified_at'] === null && self::mailOk($r)) {
                self::send($r, t('New reply in “{title}”', ['title' => $topic['title']]), t('{who} replied', ['who' => $author]), $excerpt, t('Read the reply'), '/forum/post/' . $postId, 'topic', (int) $topic['id']);
                db()->exec('UPDATE {forum_topic_subs} SET notified_at = ? WHERE user_id = ? AND topic_id = ?', [now(), $r['user_id'], $topic['id']]);
            }
        }
    }

    /** New topic → followers of the subcategory. */
    public static function onTopic(array $board, int $topicId, string $title, int $authorId, string $body): void
    {
        $author = (string) db()->val('SELECT username FROM {users} WHERE id = ?', [$authorId]);
        $rows = db()->all(
            "SELECT s.user_id, u.email, u.notify_forum, u.email_verified_at FROM {forum_board_subs} s
             JOIN {users} u ON u.id = s.user_id JOIN {roles} r ON r.id = u.role_id
             WHERE s.board_id = ? AND s.user_id <> ? AND u.status = 'active' AND r.level >= ? LIMIT " . self::MAX_RECIPIENTS,
            [$board['id'], $authorId, (int) $board['read_level']]
        );
        $excerpt = Bbcode::excerpt($body, 200);
        foreach ($rows as $r) {
            notify_user((int) $r['user_id'], 'forum_topic', t('New topic in {board}: {title}', ['board' => $board['title'], 'title' => $title]), '/forum/topic/' . $topicId, $author . ': ' . $excerpt);
            if (self::mailOk($r)) {
                self::send($r, t('New topic in {board}', ['board' => $board['title']]), $title, $author . ': ' . $excerpt, t('Open the topic'), '/forum/topic/' . $topicId, 'board', (int) $board['id']);
            }
        }
    }
}
