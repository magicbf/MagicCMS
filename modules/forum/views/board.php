<?php /** @var array $b subcategory row @var array $rows @var int $page @var int $pages @var bool $subscribed */ use MagicCMS\Modules\Forum\Forum; ?>
<?= render('forum/_crumbs', ['items' => [[$b['category_title'], url('forum/category/' . (int) $b['category_id'])], [$b['title'], null]]]) ?>
<div class="forum-head"><h1 class="admin-title"><?= e($b['title']) ?></h1>
  <div class="forum-head-actions"><?php if (user()): ?><form class="inline" method="post" action="<?= e(url('forum/subcategory/' . (int) $b['id'] . '/subscribe')) ?>"><?= csrf_field() ?><input type="hidden" name="sub" value="<?= $subscribed ? '0' : '1' ?>"><button class="btn btn-ghost"><?= $subscribed ? '🔔 ' . e(t('Following')) : '🔕 ' . e(t('Follow new topics')) ?></button></form><?php endif ?>
  <?php if (Forum::canPost($b)): ?><a class="btn btn-primary" href="<?= e(url('forum/subcategory/' . (int) $b['id'] . '/new')) ?>">✎ <?= e(t('New topic')) ?></a>
  <?php elseif (!user()): ?><a class="btn btn-ghost" href="<?= e(url('login?next=' . rawurlencode('/forum/subcategory/' . (int) $b['id']))) ?>"><?= e(t('Log in to post')) ?></a>
  <?php elseif ($b['is_locked']): ?><span class="badge">🔒 <?= e(t('Locked')) ?></span><?php endif ?></div></div>
<?php if ($b['description'] !== ''): ?><p class="muted"><?= e($b['description']) ?></p><?php endif ?>
<?= render('forum/_bar') ?>
<section class="card"><h2 class="card-title"><?= e(t('Topics')) ?></h2><div class="forum-table">
<?php foreach ($rows as $t): echo render('forum/_toprow', ['t' => $t]); endforeach; if (!$rows): ?><p class="card-body muted"><?= e(t('No topics yet. Be the first to post!')) ?></p><?php endif ?></div></section>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('forum/subcategory/' . (int) $b['id'])]) ?>
