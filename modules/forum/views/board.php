<?php /** @var array $b @var array $rows @var array $subs @var ?array $parent @var int $page @var int $pages */ use MagicCMS\Modules\Forum\Forum; ?>
<?= render('forum/_crumbs', ['items' => array_filter([$parent ? [$parent['title'], url('forum/board/' . (int) $parent['id'])] : null, [$b['title'], null]])]) ?>
<div class="forum-head"><h1 class="admin-title"><?= e($b['title']) ?></h1>
  <?php if (Forum::canPost($b)): ?><a class="btn btn-primary" href="<?= e(url('forum/board/' . (int) $b['id'] . '/new')) ?>">✎ <?= e(t('New topic')) ?></a>
  <?php elseif (!user()): ?><a class="btn btn-ghost" href="<?= e(url('login?next=' . rawurlencode('/forum/board/' . (int) $b['id']))) ?>"><?= e(t('Log in to post')) ?></a>
  <?php elseif ($b['is_locked']): ?><span class="badge">🔒 <?= e(t('Locked')) ?></span><?php endif ?></div>
<?= render('forum/_bar') ?>
<?php if ($subs): ?><section class="card"><h2 class="card-title"><?= e(t('Sub-boards')) ?></h2><div class="forum-table"><?php foreach ($subs as $sb): ?>
  <div class="forum-row board-row"><div class="fr-icon">○</div><div class="fr-main"><a class="topic-title" href="<?= e(url('forum/board/' . (int) $sb['id'])) ?>"><?= e($sb['title']) ?></a><div class="muted small"><?= e($sb['description']) ?></div></div><div class="fr-stat"><strong><?= (int) $sb['topic_count'] ?></strong><span><?= e(t('topics')) ?></span></div><div class="fr-stat"><strong><?= (int) $sb['post_count'] ?></strong><span><?= e(t('posts')) ?></span></div></div>
<?php endforeach ?></div></section><?php endif ?>
<section class="card"><h2 class="card-title"><?= e(t('Topics')) ?></h2><div class="forum-table">
<?php foreach ($rows as $t): echo render('forum/_toprow', ['t' => $t]); endforeach; if (!$rows): ?><p class="card-body muted"><?= e(t('No topics yet. Be the first to post!')) ?></p><?php endif ?></div></section>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('forum/board/' . (int) $b['id'])]) ?>
