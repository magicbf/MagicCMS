<?php /** @var array $rows */ use MagicCMS\Core\Bbcode; ?>
<?= render('forum/_crumbs', ['items' => [[t('Reported posts'), null]]]) ?>
<div class="card"><h1 class="card-title">🛡 <?= e(t('Reported posts')) ?></h1><div class="card-body">
<?php foreach ($rows as $r): ?>
  <div class="report-item"><div><a class="topic-title" href="<?= e(MagicCMS\Modules\Forum\Forum::postUrl((int) $r['post_id'])) ?>"><?= e($r['title']) ?></a> <span class="muted small"><?= e(t('post by')) ?> <?= e($r['author']) ?></span></div>
  <blockquote class="bb-quote"><?= e(Bbcode::excerpt($r['body'], 300)) ?></blockquote>
  <p><strong><?= e($r['reporter']) ?>:</strong> <?= e($r['reason']) ?> <span class="muted small">· <?= e(time_ago($r['created_at'])) ?></span></p>
  <form method="post" action="<?= e(url('forum/reports/' . (int) $r['id'] . '/close')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary"><?= e(t('Mark resolved')) ?></button></form></div>
<?php endforeach; if (!$rows): ?><p class="muted"><?= e(t('No open reports. 🎉')) ?></p><?php endif ?></div></div>
