<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

/** One reaction per member per post; clicking the same one again removes it. */
final class Reactions
{
    public const KINDS = ['like' => '👍', 'love' => '❤️', 'haha' => '😂', 'wow' => '😮', 'fire' => '🔥', 'gg' => '👏'];

    public static function enabled(): bool
    {
        return (bool) setting('forum.reactions_enabled', true);
    }

    /** @return array<int,array{counts:array<string,int>,mine:?string,names:array<string,string[]>}> */
    public static function forPosts(array $postIds): array
    {
        $postIds = array_map('intval', $postIds);
        $out = [];
        if (!$postIds) {
            return $out;
        }
        $in = implode(',', $postIds);
        foreach (db()->all("SELECT r.post_id, r.kind, r.user_id, u.username FROM {forum_reactions} r JOIN {users} u ON u.id = r.user_id WHERE r.post_id IN ($in) ORDER BY r.created_at") as $r) {
            $o = &$out[(int) $r['post_id']];
            $o['counts'][$r['kind']] = ($o['counts'][$r['kind']] ?? 0) + 1;
            if (count($o['names'][$r['kind']] ?? []) < 8) {
                $o['names'][$r['kind']][] = $r['username'];
            }
            if (user() && (int) $r['user_id'] === (int) user()['id']) {
                $o['mine'] = $r['kind'];
            }
            unset($o);
        }
        return $out;
    }

    public static function toggle(int $postId, int $userId, string $kind): ?string
    {
        if (!isset(self::KINDS[$kind])) {
            return null;
        }
        $cur = db()->val('SELECT kind FROM {forum_reactions} WHERE post_id = ? AND user_id = ?', [$postId, $userId]);
        if ($cur === $kind) {
            db()->exec('DELETE FROM {forum_reactions} WHERE post_id = ? AND user_id = ?', [$postId, $userId]);
            return null;
        }
        db()->exec('INSERT INTO {forum_reactions} (post_id, user_id, kind, created_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE kind = VALUES(kind), created_at = VALUES(created_at)', [$postId, $userId, $kind, now()]);
        return $kind;
    }

    public static function deleteForPosts(array $postIds): void
    {
        $postIds = array_map('intval', $postIds);
        if ($postIds) {
            db()->exec('DELETE FROM {forum_reactions} WHERE post_id IN (' . implode(',', $postIds) . ')');
        }
    }
}
