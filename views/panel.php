<?php /** @var array $panel @var string $html */ ?>
<section class="panel<?= !empty($panel['hideMobile']) ? ' hide-mobile' : '' ?>" data-panel="<?= e($panel['key']) ?>" data-panel-id="<?= (int) $panel['id'] ?>">
  <header class="panel-title"><h3><?= e($panel['title']) ?></h3><button class="panel-toggle" aria-label="<?= e(t('Collapse panel')) ?>" aria-expanded="true"></button></header>
  <div class="panel-body"><?= $html ?></div>
</section>
