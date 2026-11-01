<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

use MagicCMS\Core\Auth;

/** Shared forum logic: permissions, counters, unread tracking. */
final class Forum
{
    public const PER_PAGE_TOPICS = 20;
    public const PER_PAGE_POSTS = 15;
    public const LEVELS = [0 => 'Everyone (incl. guests)', 10 => 'Members', 50 => 'Moderators', 80 => 'Administrators'];

    public static function isMod(): bool
    {
        return can('forum.moderate');
    }

    public static function canRead(array $board): bool
    {
        return (int) $board['read_level'] <= Auth::level();
    }

    public static function canPost(array $board): bool
    {
        return user() !== null && can('forum.post') && (int) $board['post_level'] <= Auth::level()
            && (!$board['is_locked'] || self::isMod());
    }

    public static function board(int $id): ?array
    {
        return db()->one('SELECT b.*, c.title AS category_title FROM {forum_boards} b JOIN {forum_categories} c ON c.id = b.category_id WHERE b.id = ?', [$id]);
    }

    /** Topic + its board; null when missing or the board is not readable by the current user. */
    public static function topic(int $id): ?array
    {
        $t = db()->one(
            'SELECT t.*, b.title AS board_title, b.read_level, b.post_level, b.is_locked AS board_locked, b.parent_id AS board_parent, b.category_id
             FROM {forum_topics} t JOIN {forum_boards} b ON b.id = t.board_id WHERE t.id = ?',
            [$id]
        );
        return $t && (int) $t['read_level'] <= Auth::level() ? $t : null;
    }

    public static function canReply(array $topic): bool
    {
        $board = ['read_level' => $topic['read_level'], 'post_level' => $topic['post_level'], 'is_locked' => $topic['board_locked']];
        return self::canPost($board) && (!$topic['is_locked'] || self::isMod());
    }

    /** Recompute a topic's counters from its posts; deletes the topic if it has none. */
    public static function syncTopic(int $topicId): void
    {
        $r = db()->one('SELECT COUNT(*) c, MIN(id) f, MAX(id) l, MAX(created_at) at FROM {forum_posts} WHERE topic_id = ?', [$topicId]);
        if (!$r || (int) $r['c'] === 0) {
            db()->exec('DELETE FROM {forum_reads} WHERE topic_id = ?', [$topicId]);
            db()->exec('DELETE FROM {forum_topics} WHERE id = ?', [$topicId]);
            return;
        }
        db()->exec(
            'UPDATE {forum_topics} SET reply_count = ?, first_post_id = ?, last_post_id = ?, last_post_at = ? WHERE id = ?',
            [(int) $r['c'] - 1, $r['f'], $r['l'], $r['at'], $topicId]
        );
    }

    public static function syncBoard(int $boardId): void
    {
        $r = db()->one('SELECT COUNT(*) t, COALESCE(SUM(reply_count + 1), 0) p FROM {forum_topics} WHERE board_id = ?', [$boardId]);
        $last = (int) db()->val('SELECT last_post_id FROM {forum_topics} WHERE board_id = ? ORDER BY last_post_at DESC, id DESC LIMIT 1', [$boardId]);
        db()->exec('UPDATE {forum_boards} SET topic_count = ?, post_count = ?, last_post_id = ? WHERE id = ?', [(int) $r['t'], (int) $r['p'], $last, $boardId]);
    }

    /** @param int[] $ids */
    public static function recountUsers(array $ids): void
    {
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            db()->exec('UPDATE {users} SET forum_posts = (SELECT COUNT(*) FROM {forum_posts} WHERE user_id = ?) WHERE id = ?', [$id, $id]);
        }
    }

    public static function deleteTopic(int $topicId): void
    {
        $users = array_column(db()->all('SELECT DISTINCT user_id FROM {forum_posts} WHERE topic_id = ?', [$topicId]), 'user_id');
        $postIds = array_column(db()->all('SELECT id FROM {forum_posts} WHERE topic_id = ?', [$topicId]), 'id');
        if ($postIds) {
            db()->exec('DELETE FROM {forum_reports} WHERE post_id IN (' . implode(',', array_map('intval', $postIds)) . ')');
        }
        db()->exec('DELETE FROM {forum_posts} WHERE topic_id = ?', [$topicId]);
        db()->exec('DELETE FROM {forum_reads} WHERE topic_id = ?', [$topicId]);
        db()->exec('DELETE FROM {forum_topics} WHERE id = ?', [$topicId]);
        self::recountUsers($users);
    }

    /** Posts after this time are "new" unless explicitly read (registration or last "mark all read"). */
    public static function readSince(): string
    {
        $u = user();
        $mark = db()->val('SELECT marked_at FROM {forum_markread} WHERE user_id = ?', [$u['id']]);
        return max((string) $u['created_at'], (string) ($mark ?: ''));
    }

    /** SQL expression (needs LEFT JOIN {forum_reads} r) that is truthy for unread topics; params appended in order. */
    public static function unreadExpr(array &$params): string
    {
        if (!user()) {
            return '0';
        }
        $params[] = self::readSince();
        return '((r.last_post_id IS NULL AND t.last_post_at > ?) OR r.last_post_id < t.last_post_id)';
    }

    public static function markRead(int $topicId, int $lastPostId): void
    {
        if (!user()) {
            return;
        }
        db()->exec(
            'INSERT INTO {forum_reads} (user_id, topic_id, last_post_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE last_post_id = GREATEST(last_post_id, VALUES(last_post_id))',
            [user()['id'], $topicId, $lastPostId]
        );
    }

    public static function rank(int $posts): string
    {
        return match (true) {
            $posts >= 500 => t('Legend'),
            $posts >= 150 => t('Veteran'),
            $posts >= 50 => t('Regular'),
            $posts >= 10 => t('Active'),
            default => t('Newcomer'),
        };
    }

    /** Page (1-based) on which a post appears in its topic. */
    public static function pageOfPost(int $topicId, int $postId): int
    {
        $pos = (int) db()->val('SELECT COUNT(*) FROM {forum_posts} WHERE topic_id = ? AND id <= ?', [$topicId, $postId]);
        return max(1, (int) ceil($pos / self::PER_PAGE_POSTS));
    }

    public static function postUrl(int $postId): string
    {
        return url('forum/post/' . $postId);
    }

    /** Flood protection: seconds the user must still wait before posting again. */
    public static function floodWait(): int
    {
        $last = db()->val('SELECT MAX(created_at) FROM {forum_posts} WHERE user_id = ?', [user()['id']]);
        return $last ? max(0, 10 - (time() - (int) strtotime($last . ' UTC'))) : 0;
    }
}
