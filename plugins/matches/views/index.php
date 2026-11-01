<?php /** @var array $rows @var array $upcoming @var array $stats @var array $games @var string $result @var string $game @var int $page @var int $pages @var string $base */ ?>
<div class="page-intro"><div><span class="eyebrow"><?= e(t('Competition')) ?></span><h1 class="page-title"><?= e(t('Matches')) ?></h1></div></div>
<div class="stat-grid">
  <div class="stat-tile"><span><?= e(t('Played')) ?></span><strong><?= (int) $stats['played'] ?></strong></div>
  <div class="stat-tile"><span><?= e(t('Record W–L–D')) ?></span><strong><?= (int) $stats['wins'] ?>–<?= (int) $stats['losses'] ?>–<?= (int) $stats['draws'] ?></strong></div>
  <div class="stat-tile"><span><?= e(t('Win rate')) ?></span><strong><?= (int) $stats['rate'] ?>%</strong></div>
  <div class="stat-tile is-hot"><span><?= e(t('Streak')) ?></span><strong><?= e($stats['streak']) ?></strong></div>
</div>
<?php if ($upcoming): ?><section class="card"><h2 class="card-title"><?= e(t('Upcoming')) ?></h2><div class="match-list"><?php foreach ($upcoming as $m): echo render('matches/_row', ['m' => $m]); endforeach ?></div></section><?php endif ?>
<section class="card"><h2 class="card-title"><?= e(t('Results')) ?></h2>
  <form class="card-body inline-form" method="get" action="<?= e(url('matches')) ?>">
    <select class="select" name="result" aria-label="<?= e(t('Result')) ?>"><option value=""><?= e(t('All results')) ?></option><?php foreach (['win' => 'Wins', 'loss' => 'Losses', 'draw' => 'Draws'] as $k => $l): ?><option value="<?= $k ?>" <?= $result === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select>
    <?php if (count($games) > 1): ?><select class="select" name="game" aria-label="<?= e(t('Game')) ?>"><option value=""><?= e(t('All games')) ?></option><?php foreach ($games as $g): ?><option <?= $game === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach ?></select><?php endif ?>
    <button class="btn btn-sm btn-primary"><?= e(t('Filter')) ?></button><?php if ($result || $game): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('matches')) ?>"><?= e(t('Reset')) ?></a><?php endif ?></form>
  <div class="match-list"><?php foreach ($rows as $m): echo render('matches/_row', ['m' => $m]); endforeach; if (!$rows): ?><p class="card-body muted"><?= e(t('No matches found.')) ?></p><?php endif ?></div>
</section>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => $base]) ?>
