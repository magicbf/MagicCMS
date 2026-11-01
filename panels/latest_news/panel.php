<?php
/** @var array $settings */
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'news' AND enabled = 1")) return;
$rows = MagicCMS\Modules\News\News::latest((int) $settings['count'], preg_replace('/[^a-z0-9\-]/', '', (string) $settings['category']) ?: null);
if (!$rows) return; ?>
<ul class="news-mini">
<?php foreach ($rows as $p): ?>
  <li style="--cat-color:<?= e(MagicCMS\Modules\News\News::catColor($p)) ?>"><a class="nm-thumb" href="<?= e(url('news/' . $p['slug'])) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e(MagicCMS\Modules\News\News::coverUrl($p)) ?>" alt="" width="72" height="48" loading="lazy"></a>
    <div><?php if ($p['cat_name']): ?><span class="cat-badge sm"><?= e($p['cat_name']) ?></span><?php endif ?><a class="nm-title" href="<?= e(url('news/' . $p['slug'])) ?>"><?= e($p['title']) ?></a><span class="muted small"><?= e(time_ago($p['publish_at'])) ?></span></div></li>
<?php endforeach ?>
</ul>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('news')) ?>"><?= e(t('All news')) ?></a>
