<?php /** @var int $page @var int $pages @var string $base */
if ($pages <= 1) return;
$sep = str_contains($base, '?') ? '&' : '?';
$range = array_unique(array_filter([1, 2, $page - 1, $page, $page + 1, $pages - 1, $pages], fn($p) => $p >= 1 && $p <= $pages));
sort($range); $prev = 0; ?>
<nav class="pagination" aria-label="Pagination">
<?php foreach ($range as $p): if ($prev && $p - $prev > 1): ?><span class="gap">…</span><?php endif; $prev = $p; ?>
  <a class="<?= $p === $page ? 'is-current' : '' ?>" href="<?= e($base . $sep . 'page=' . $p) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
<?php endforeach ?>
</nav>
