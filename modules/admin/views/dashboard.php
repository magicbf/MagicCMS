<?php /** @var array $stats @var array $log @var array $sys @var bool $setupLeft */ ?>
<?php if ($setupLeft): ?><div class="alert alert-warning">⚠ <strong><?= e(t('Delete setup.php from your server.')) ?></strong> <?= e(t('The installer is locked, but the file should not stay on a live site.')) ?></div><?php endif ?>
<div class="stat-grid"><?php foreach ($stats as $label => $n): ?><div class="stat-tile"><span><?= e($label) ?></span><strong><?= (int) $n ?></strong></div><?php endforeach ?></div>
<div class="admin-cols">
<div class="card"><h2 class="card-title"><?= e(t('Recent activity')) ?></h2><div class="card-body table-wrap"><table class="table">
<?php foreach ($log as $l): ?><tr><td><?= e(fmt_date($l['created_at'])) ?></td><td><?= e($l['username'] ?? '—') ?></td><td><code><?= e($l['action']) ?></code></td><td><?= e($l['detail']) ?></td></tr><?php endforeach ?>
<?php if (!$log): ?><tr><td class="muted"><?= e(t('Nothing yet.')) ?></td></tr><?php endif ?></table></div></div>
<div class="card"><h2 class="card-title"><?= e(t('System')) ?></h2><div class="card-body"><table class="table"><?php foreach ($sys as $k => $v): ?><tr><td><?= e($k) ?></td><td><?= e($v) ?></td></tr><?php endforeach ?></table></div></div>
</div>
