<?php /** @var array $list */
use MagicCMS\Modules\Forum\Attachments;
$imgs = array_filter($list, fn($a) => $a['is_image']); $files = array_filter($list, fn($a) => !$a['is_image']); ?>
<div class="post-attachments">
<?php if ($imgs): ?><div class="att-grid"><?php foreach ($imgs as $a): $full = url('forum/attachment/' . (int) $a['id']); ?><a class="att-img" href="<?= e($full) ?>" target="_blank" rel="noopener" title="<?= e($a['orig_name']) ?>"><img src="<?= e($a['thumb'] ? $full . '?thumb=1' : $full) ?>" alt="<?= e($a['orig_name']) ?>" loading="lazy"></a><?php endforeach ?></div><?php endif ?>
<?php foreach ($files as $a): ?><a class="att-file" href="<?= e(url('forum/attachment/' . (int) $a['id'])) ?>" rel="nofollow">📄 <strong><?= e($a['orig_name']) ?></strong> <span class="muted"><?= e(Attachments::size((int) $a['size'])) ?> · <?= (int) $a['downloads'] ?>×</span></a><?php endforeach ?>
</div>
