<?php /** @var array $p post row with cat_* fields  @var bool $big */
use MagicCMS\Modules\News\News; $color = News::catColor($p); ?>
<article class="news-card<?= !empty($big) ? ' news-featured' : '' ?>" style="--cat-color:<?= e($color) ?>">
  <a class="news-cover" href="<?= e(url('news/' . $p['slug'])) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e(News::coverUrl($p)) ?>" alt="" loading="lazy" width="640" height="360"><?php if ($p['featured']): ?><span class="news-flag">★ <?= e(t('Featured')) ?></span><?php endif ?></a>
  <div class="news-body">
    <div class="news-meta"><?php if ($p['cat_name']): ?><a class="cat-badge" href="<?= e(url('news/category/' . $p['cat_slug'])) ?>"><?= $p['cat_icon'] !== '' ? e($p['cat_icon']) . ' ' : '' ?><?= e($p['cat_name']) ?></a><?php endif ?><span><?= e($p['author']) ?> · <?= e(time_ago($p['publish_at'])) ?></span></div>
    <h3 class="news-title"><a href="<?= e(url('news/' . $p['slug'])) ?>"><?= e($p['title']) ?></a></h3>
    <?php if ($p['excerpt'] !== ''): ?><p class="news-excerpt"><?= e($p['excerpt']) ?></p><?php endif ?>
    <div class="news-foot muted small"><span>💬 <?= (int) $p['comment_count'] ?></span><span>👁 <?= (int) $p['views'] ?></span></div>
  </div>
</article>
