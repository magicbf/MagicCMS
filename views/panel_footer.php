<?php /** Panel rendered as a footer column (no card chrome). @var array $panel @var string $html */ ?>
<section class="fcol fpanel" data-panel="<?= e($panel['key']) ?>"><h4><?= e($panel['title']) ?></h4><div class="fpanel-body"><?= $html ?></div></section>
