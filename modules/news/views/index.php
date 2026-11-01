<?php /** @var array $rows @var array $cats @var ?array $cat @var ?string $tag @var int $page @var int $pages @var string $base */ ?>
<div class="page-intro"><div><span class="eyebrow"><?= e(t('Newsroom')) ?></span><h1 class="page-title"><?= e($cat ? $cat['name'] : ($tag ? '#' . $tag : t('News'))) ?></h1></div>
  <a class="btn btn-ghost btn-sm" href="<?= e(url('news/feed.xml')) ?>" title="RSS">RSS</a></div>
<?php if ($cat && $cat['description'] !== ''): ?><p class="muted"><?= e($cat['description']) ?></p><?php endif ?>
<div class="chip-row" role="group" aria-label="<?= e(t('Filter by category')) ?>">
  <a class="chip<?= !$cat && !$tag ? ' is-active' : '' ?>" href="<?= e(url('news')) ?>"><?= e(t('All')) ?></a>
  <?php foreach ($cats as $c): if (!$c['posts']) continue; ?>
    <a class="chip<?= $cat && (int) $cat['id'] === (int) $c['id'] ? ' is-active' : '' ?>" style="--cat-color:<?= e($c['color']) ?>" href="<?= e(url('news/category/' . $c['slug'])) ?>"><i class="chip-dot"></i><?= $c['icon'] !== '' ? e($c['icon']) . ' ' : '' ?><?= e($c['name']) ?></a>
  <?php endforeach ?>
</div>
<?php if (!$rows): ?><div class="card"><div class="card-body muted"><?= e(t('No posts yet.')) ?></div></div>
<?php else: ?><div class="news-grid"><?php foreach ($rows as $i => $p): echo render('news/_card', ['p' => $p, 'big' => $i === 0 && $page === 1 && !$cat && !$tag && $p['featured']]); endforeach ?></div><?php endif ?>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => $base]) ?>
