<?php /** @var array $items [[label, url|null], …] */ ?>
<nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('forum')) ?>"><?= e(t('Forum')) ?></a><?php foreach ($items as [$label, $href]): ?> <span class="sep">›</span> <?php if ($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span><?= e($label) ?></span><?php endif ?><?php endforeach ?></nav>
