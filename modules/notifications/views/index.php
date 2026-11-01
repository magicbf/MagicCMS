<?php /** @var array $rows @var int $page @var int $pages */ use MagicCMS\Modules\Notifications\Notifications; ?>
<div class="page-intro"><div><span class="eyebrow"><?= e(t('Activity')) ?></span><h1 class="page-title"><?= e(t('Notifications')) ?></h1></div>
  <div class="inline-form"><form method="post" action="<?= e(url('notifications/read-all')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e(t('Mark all read')) ?></button></form>
  <form method="post" action="<?= e(url('notifications/clear')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e(t('Clear read')) ?></button></form></div></div>
<section class="card"><div class="nt-list">
<?php foreach ($rows as $n): ?>
  <a class="nt-row<?= $n['read_at'] === null ? ' is-unread' : '' ?>" href="<?= e(url('notifications/go/' . (int) $n['id'])) ?>">
    <span class="nt-icon" aria-hidden="true"><?= e(Notifications::ICONS[$n['type']] ?? Notifications::ICONS['generic']) ?></span>
    <span class="nt-main"><strong><?= e($n['title']) ?></strong><?php if ($n['body'] !== ''): ?><span class="muted small"><?= e($n['body']) ?></span><?php endif ?></span>
    <span class="muted small nt-time"><?= e(time_ago($n['created_at'])) ?></span>
  </a>
<?php endforeach; if (!$rows): ?><p class="card-body muted"><?= e(t('You are all caught up. 🎉')) ?></p><?php endif ?></div></section>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('notifications')]) ?>
