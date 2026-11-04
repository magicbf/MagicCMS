<?php /** @var array $groups @var array $former @var int $count */ ?>
<div class="page-intro"><div><span class="eyebrow"><?= e(t('The team')) ?></span><h1 class="page-title"><?= e(t('Roster')) ?></h1></div>
  <span class="live-dot"><i></i><?= e(t('{n} active members', ['n' => $count])) ?></span></div>
<?php foreach ($groups as $name => $members): ?>
<section class="rm-group"><h2 class="section-title"><?= e($name) ?></h2><div class="rm-grid"><?php foreach ($members as $m): echo render('roster/_card', ['m' => $m]); endforeach ?></div></section>
<?php endforeach; if (!$groups): ?><div class="card"><div class="card-body muted"><?= e(t('The roster is empty.')) ?></div></div><?php endif ?>
<?php if ($former): ?><details class="rm-former"><summary class="section-title"><?= e(t('Former members')) ?> (<?= count($former) ?>)</summary><div class="rm-grid rm-dim"><?php foreach ($former as $m): echo render('roster/_card', ['m' => $m]); endforeach ?></div></details><?php endif ?>
