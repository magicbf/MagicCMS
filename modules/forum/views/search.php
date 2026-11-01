<?php /** @var string $q @var array $rows */ use MagicCMS\Core\Bbcode; ?>
<?= render('forum/_crumbs', ['items' => [[t('Search'), null]]]) ?>
<?= render('forum/_bar', ['q' => $q]) ?>
<div class="card"><h1 class="card-title"><?= e(t('Search results')) ?> <?= $q !== '' ? '<small>“' . e($q) . '”</small>' : '' ?></h1><div class="card-body">
<?php if (mb_strlen($q) < 3): ?><p class="muted"><?= e(t('Enter at least 3 characters.')) ?></p>
<?php elseif (!$rows): ?><p class="muted"><?= e(t('Nothing found.')) ?></p>
<?php else: foreach ($rows as $r): ?>
  <div class="search-hit"><a class="topic-title" href="<?= e(MagicCMS\Modules\Forum\Forum::postUrl((int) $r['id'])) ?>"><?= e($r['title']) ?></a>
  <div class="muted small"><?= e($r['board']) ?> · <?= e(t('by')) ?> <?= e($r['username']) ?> · <?= e(time_ago($r['created_at'])) ?></div><p><?= e(Bbcode::excerpt($r['body'], 220)) ?></p></div>
<?php endforeach; endif ?></div></div>
