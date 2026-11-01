<?php
/** @var array $settings */
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'forum' AND enabled = 1")) return;
$rows = db()->all(
    'SELECT t.id, t.title, t.reply_count, t.last_post_at, u.username FROM {forum_topics} t
     JOIN {forum_boards} b ON b.id = t.board_id JOIN {forum_posts} p ON p.id = t.last_post_id JOIN {users} u ON u.id = p.user_id
     WHERE b.read_level <= ? ORDER BY t.last_post_at DESC LIMIT ' . max(1, min(20, (int) $settings['count'])),
    [MagicCMS\Core\Auth::level()]
);
if (!$rows) return; ?>
<ul class="latest-list">
<?php foreach ($rows as $r): ?>
  <li><a class="clip" href="<?= e(url('forum/topic/' . (int) $r['id'] . '/last')) ?>"><?= e($r['title']) ?></a>
  <span class="muted small"><?= e($r['username']) ?> · <?= e(time_ago($r['last_post_at'])) ?> · <?= (int) $r['reply_count'] ?> 💬</span></li>
<?php endforeach ?>
</ul>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('forum')) ?>"><?= e(t('Open forum')) ?></a>
