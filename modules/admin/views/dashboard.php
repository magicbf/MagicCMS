<?php /** @var array $stats @var array $log @var array $sys @var bool $setupLeft */
$u = user(); $h = (int) date('G'); $greet = $h < 12 ? t('Good morning') : ($h < 18 ? t('Good afternoon') : t('Good evening'));
$ico = ['<circle cx="9" cy="8" r="3.4"/><path d="M2.8 20c0-3.5 2.8-5.8 6.2-5.8s6.2 2.3 6.2 5.8"/><path d="M16 5.2a3.4 3.4 0 010 6.4M18.2 14.6c2 .7 3.2 2.6 3.2 5.4"/>',
        '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
        '<path d="M4 5.5A1.5 1.5 0 015.5 4h13A1.5 1.5 0 0120 5.5v9a1.5 1.5 0 01-1.5 1.5H10l-4.5 4v-4A1.5 1.5 0 014 14.5z"/>'];
$quick = [['/admin/pages', t('New page'), 'admin.pages'], ['/admin/appearance', t('Appearance'), 'admin.appearance'], ['/admin/settings', t('Settings'), 'admin.settings']]; ?>
<?php if (can('admin.system')): MagicCMS\Core\Updater::autoCheck(); $upd = MagicCMS\Core\Updater::status(); if (!empty($upd['newer'])): ?><div class="alert alert-info">⬆ <span><strong><?= e(t('A MagicCMS update is available.')) ?></strong> <?= e(!empty($upd['short']) ? 'v' . ($upd['version'] ?? '') . ' · ' . $upd['short'] : 'v' . ($upd['version'] ?? '')) ?> <a href="<?= e(url('admin/maintenance/update')) ?>"><?= e(t('Review the update')) ?></a> – <?= e(t('you see exactly what changes before anything is installed.')) ?></span></div><?php endif; endif ?>
<?php if ($setupLeft): ?><div class="alert alert-warning">⚠ <span><strong><?= e(t('Delete setup.php from your server.')) ?></strong> <?= e(t('The installer is locked, but the file should not stay on a live site.')) ?></span></div><?php endif ?>
<section class="adm-hello">
  <div><h2><?= e($greet) ?>, <?= e($u['username'] ?? '') ?> 👋</h2><p><?= e(t('Here is what is happening on {site} right now.', ['site' => setting('site.name', 'MagicCMS')])) ?></p></div>
  <div class="adm-quick"><?php foreach ($quick as [$href, $label, $perm]): if (!can($perm)) continue; ?><a class="btn btn-sm<?= $href === '/admin/pages' ? ' btn-primary' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a><?php endforeach ?></div>
</section>
<div class="stat-grid"><?php $i = 0; foreach ($stats as $label => $n): ?><div class="stat-tile"><svg class="st-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $ico[$i++ % count($ico)] ?></svg><span><?= e($label) ?></span><strong><?= (int) $n ?></strong></div><?php endforeach ?></div>
<div class="adm-cols">
<div class="card"><h2 class="card-title"><?= e(t('Recent activity')) ?></h2>
<?php if ($log): ?><ul class="adm-feed"><?php foreach ($log as $l): ?><li><i class="dot"></i><div><b><?= e($l['username'] ?? '—') ?></b> <code><?= e($l['action']) ?></code><?php if ((string) $l['detail'] !== ''): ?><span class="d"><?= e($l['detail']) ?></span><?php endif ?></div><small><?= e(fmt_date($l['created_at'])) ?></small></li><?php endforeach ?></ul>
<?php else: ?><div class="adm-empty"><?= e(t('Nothing yet.')) ?></div><?php endif ?></div>
<div class="card"><h2 class="card-title"><?= e(t('System')) ?></h2><ul class="adm-sys"><?php foreach ($sys as $k => $v): ?><li><span><?= e($k) ?></span><b><?= e($v) ?></b></li><?php endforeach ?></ul></div>
</div>
