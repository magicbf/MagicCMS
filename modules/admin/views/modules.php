<?php /** @var array $rows */ ?>
<p class="muted"><?= e(t('Core modules are built in. Plugins live in the plugins/ folder and can be switched on and off here. Only the Superadministrator sees this page.')) ?></p>
<div class="card"><div class="card-body table-wrap"><table class="table"><thead><tr><th><?= e(t('Name')) ?></th><th><?= e(t('Type')) ?></th><th><?= e(t('Version')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['name']) ?></strong><div class="muted small"><?= e($r['description']) ?> <code><?= e($r['module_key']) ?></code></div></td>
<td><span class="badge"><?= $r['is_core'] ? e(t('Core')) : e(t('Plugin')) ?></span></td><td><?= e($r['version']) ?></td>
<td><?= !$r['present'] ? '<span class="badge">' . e(t('Files missing')) . '</span>' : ($r['enabled'] ? '<span class="badge role-member">' . e(t('Enabled')) . '</span>' : '<span class="badge">' . e(t('Disabled')) . '</span>') ?></td>
<td><?php if (!$r['is_core']): ?><form method="post" action="<?= e(url('admin/modules/toggle/' . $r['module_key'])) ?>"><?= csrf_field() ?><button class="btn btn-sm <?= $r['enabled'] ? 'btn-ghost' : 'btn-primary' ?>"><?= e($r['enabled'] ? t('Disable') : t('Enable')) ?></button></form><?php endif ?></td></tr>
<?php endforeach ?></tbody></table></div></div>
