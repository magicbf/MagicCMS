<?php /** @var array $menu @var array $menus @var array $items @var ?array $item @var array $tops @var array $pages @var array $targets */ ?>
<div class="tabs sub"><?php foreach ($menus as $m): ?><a class="tab<?= $m['id'] === $menu['id'] ? ' is-active' : '' ?>" href="<?= e(url('admin/navigation?menu=' . $m['menu_key'])) ?>"><?= e($m['title']) ?></a><?php endforeach ?></div>
<div class="card"><h2 class="card-title"><?= e($menu['title']) ?></h2><div class="card-body table-wrap">
<table class="table"><thead><tr><th><?= e(t('Label')) ?></th><th><?= e(t('Target')) ?></th><th><?= e(t('Visible')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($items as $i): $v = json_decode((string) $i['visibility'], true) ?: []; ?>
<tr><td><?= $i['parent_id'] ? '↳ ' : '' ?><?= e($i['icon']) ?> <strong><?= e($i['label']) ?></strong><?= $i['badge'] !== '' ? ' <span class="badge">' . e($i['badge']) . '</span>' : '' ?></td>
<td><code><?= e($i['target_type']) ?></code> <?= e($i['target_type'] === 'action' ? 'login/profile' : $i['target_ref']) ?></td><td><?= e($v['who'] ?? 'all') ?><?= empty($v['mobile'] ?? true) ? ' · ' . e(t('hidden on mobile')) : '' ?></td>
<td class="actions">
  <form method="post" action="<?= e(url('admin/navigation/move/' . (int) $i['id'] . '/up')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Up">▲</button></form>
  <form method="post" action="<?= e(url('admin/navigation/move/' . (int) $i['id'] . '/down')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Down">▼</button></form>
  <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/navigation/' . (int) $i['id'])) ?>"><?= e(t('Edit')) ?></a>
  <form method="post" action="<?= e(url('admin/navigation/delete/' . (int) $i['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this item?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$items): ?><tr><td colspan="4" class="muted"><?= e(t('No items yet.')) ?></td></tr><?php endif ?></tbody></table></div></div>
<h2 class="section-title"><?= e(t('Add button')) ?></h2>
<?= render('admin/_nav_form', compact('menu', 'item', 'tops', 'pages', 'targets')) ?>
