<?php
/** @var array $settings */
$since = gmdate('Y-m-d H:i:s', time() - 60 * max(1, (int) setting('online.window_min', 5)));
$members = db()->all(
    'SELECT DISTINCT u.id, u.username, u.avatar, r.slug AS role_slug FROM {online_sessions} o
     JOIN {users} u ON u.id = o.user_id JOIN {roles} r ON r.id = u.role_id
     WHERE o.last_seen > ? ORDER BY r.level DESC, u.username LIMIT ' . max(1, min(100, (int) $settings['max'])),
    [$since]
);
$guests = (int) db()->val('SELECT COUNT(*) FROM {online_sessions} WHERE last_seen > ? AND user_id IS NULL', [$since]);
?>
<p class="muted"><?= e(t('{m} member(s), {g} guest(s)', ['m' => count($members), 'g' => $guests])) ?></p>
<ul class="online-list">
<?php foreach ($members as $m): ?>
  <li><a href="<?= e(url('user/' . $m['username'])) ?>"><img class="avatar avatar-xs" src="<?= e(avatar_url($m, 32)) ?>" alt="" width="24" height="24" loading="lazy"><span class="role-<?= e($m['role_slug']) ?>"><?= e($m['username']) ?></span></a></li>
<?php endforeach ?>
</ul>
