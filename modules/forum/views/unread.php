<?php /** @var array $rows */ ?>
<?= render('forum/_crumbs', ['items' => [[t('Unread topics'), null]]]) ?>
<?= render('forum/_bar') ?>
<section class="card"><h1 class="card-title"><?= e(t('Unread topics')) ?></h1><div class="forum-table">
<?php foreach ($rows as $t): echo render('forum/_toprow', ['t' => $t]); endforeach; if (!$rows): ?><p class="card-body muted"><?= e(t('You are all caught up. 🎉')) ?></p><?php endif ?></div></section>
