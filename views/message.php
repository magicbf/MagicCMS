<?php /** @var string $title @var string $text */ ?>
<div class="card center"><h1 class="card-title"><?= e($title) ?></h1><div class="card-body"><p><?= e($text) ?></p><a class="btn btn-primary" href="<?= e(url('')) ?>"><?= e(t('Back to home')) ?></a></div></div>
