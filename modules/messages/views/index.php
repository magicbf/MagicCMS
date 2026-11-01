<?php /** @var array $threads @var array $blocked @var int $page @var int $pages */ use MagicCMS\Core\Bbcode; ?>
<div class="page-intro"><div><span class="eyebrow"><?= e(t('Inbox')) ?></span><h1 class="page-title"><?= e(t('Messages')) ?></h1></div>
  <?php if (can('pm.send')): ?><a class="btn btn-primary" href="<?= e(url('messages/new')) ?>">✎ <?= e(t('New message')) ?></a><?php endif ?></div>
<section class="card"><h2 class="card-title"><?= e(t('Conversations')) ?></h2><div class="pm-list">
<?php foreach ($threads as $t): ?>
  <a class="pm-row<?= $t['unread'] ? ' is-unread' : '' ?>" href="<?= e(url('messages/' . (int) $t['id'])) ?>">
    <span class="pm-avatars"><?php foreach (array_slice($t['people'], 0, 3) as $u): ?><img class="avatar avatar-md" src="<?= e(avatar_url($u, 64)) ?>" alt="" width="44" height="44" loading="lazy"><?php endforeach ?></span>
    <span class="pm-main"><strong class="pm-subject"><?= e($t['subject']) ?></strong><span class="muted small"><?= e(implode(', ', array_column($t['people'], 'username'))) ?></span>
      <span class="pm-preview muted"><?= (int) $t['last_user'] === (int) user()['id'] ? e(t('You')) . ': ' : '' ?><?= e(Bbcode::excerpt($t['last_body'], 90)) ?></span></span>
    <span class="pm-meta muted small"><?= e(time_ago($t['updated_at'])) ?><?php if ($t['unread']): ?><b class="pm-count"><?= (int) $t['unread'] ?></b><?php endif ?></span>
  </a>
<?php endforeach; if (!$threads): ?><p class="card-body muted"><?= e(t('No messages yet.')) ?></p><?php endif ?></div></section>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('messages')]) ?>
<?php if ($blocked): ?><section class="card"><h2 class="card-title"><?= e(t('Blocked users')) ?></h2><div class="card-body inline-form"><?php foreach ($blocked as $b): ?>
  <form method="post" action="<?= e(url('messages/unblock/' . (int) $b['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e($b['username']) ?> ✕</button></form><?php endforeach ?></div></section><?php endif ?>
