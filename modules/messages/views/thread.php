<?php /** @var array $t @var array $msgs @var array $people @var int $lastRead @var array $blockedIds */ use MagicCMS\Core\Bbcode; $me = (int) user()['id']; ?>
<nav class="breadcrumb"><a href="<?= e(url('messages')) ?>"><?= e(t('Messages')) ?></a> <span class="sep">›</span> <span><?= e($t['subject']) ?></span></nav>
<div class="forum-head"><h1 class="admin-title"><?= e($t['subject']) ?></h1>
  <form method="post" action="<?= e(url('messages/' . (int) $t['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this conversation from your inbox?')) ?>"><?= e(t('Delete')) ?></button></form></div>
<div class="pm-people muted small"><span><?= e(t('Between')) ?>:</span>
  <?php foreach ($people as $u): $isBlocked = in_array((string) $u['id'], array_map('strval', $blockedIds), true); ?>
    <span class="pm-person"><img class="avatar avatar-xs" src="<?= e(avatar_url($u, 32)) ?>" alt="" width="24" height="24"><a href="<?= e(url('user/' . $u['username'])) ?>"><?= e($u['username']) ?></a><?php if ((int) $u['id'] !== $me): ?><form class="inline" method="post" action="<?= e($isBlocked ? url('messages/unblock/' . (int) $u['id']) : url('messages/block/' . (int) $u['id'])) ?>"><?= csrf_field() ?><button class="link-btn" title="<?= e($isBlocked ? t('Unblock this user') : t('Block this user')) ?>" aria-label="<?= e($isBlocked ? t('Unblock this user') : t('Block this user')) ?>"><?= $isBlocked ? '🔓' : '🚫' ?></button></form><?php endif ?></span>
  <?php endforeach ?></div>
<?php foreach ($msgs as $m): $mine = (int) $m['user_id'] === $me; ?>
<article class="pm-msg<?= $mine ? ' is-mine' : '' ?><?= !$mine && (int) $m['id'] > $lastRead ? ' is-new' : '' ?>" id="m<?= (int) $m['id'] ?>">
  <img class="avatar avatar-md" src="<?= e(avatar_url(['id' => $m['user_id'], 'avatar' => $m['avatar']], 64)) ?>" alt="" width="44" height="44" loading="lazy">
  <div class="pm-bubble"><div class="pm-head"><a class="role-<?= e($m['role_slug']) ?>" href="<?= e(url('user/' . $m['username'])) ?>"><strong><?= e($m['username']) ?></strong></a><span class="muted small"><?= e(fmt_date($m['created_at'])) ?></span></div>
    <div class="post-body"><?= Bbcode::render($m['body']) ?></div></div>
</article>
<?php endforeach ?>
<span id="end"></span>
<?php if (can('pm.send')): ?>
<form class="card" method="post" action="<?= e(url('messages/' . (int) $t['id'] . '/reply')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Reply')) ?></h2><div class="card-body">
  <?= render('forum/_editor', ['body' => '', 'rows' => 5]) ?><p class="mt"><button class="btn btn-primary"><?= e(t('Send reply')) ?></button></p></div></form>
<?php endif ?>
