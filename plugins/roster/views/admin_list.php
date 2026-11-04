<?php /** @var array $rows */ ?>
<p><a class="btn btn-primary" href="<?= e(url('admin/roster/new')) ?>">+ <?= e(t('New member')) ?></a> <a class="btn btn-ghost" href="<?= e(url('admin/roster/settings')) ?>">⚙ <?= e(t('Panel & roles')) ?></a> <a class="btn btn-ghost" href="<?= e(url('roster')) ?>"><?= e(t('Public page')) ?> →</a></p>
<div class="card"><div class="card-body table-wrap"><table class="table"><thead><tr><th></th><th><?= e(t('Nickname')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Position')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $m): $ph = upload_url('roster', $m['photo']); ?><tr class="<?= $m['is_active'] ? '' : 'is-off' ?>">
<td><?php if ($ph): ?><img class="slide-thumb" style="width:40px;height:50px" src="<?= e($ph) ?>" alt="" loading="lazy"><?php else: ?><span class="rm-ph sm"><?= e(MagicCMS\Plugins\Roster\Roster::initials($m['nickname'])) ?></span><?php endif ?></td>
<td><strong><?= e($m['nickname']) ?></strong> <?= e(MagicCMS\Plugins\Roster\Roster::flag($m['country'])) ?></td><td><?= e($m['role']) ?></td><td><?php if ($m['position'] !== ''): ?><span class="pos-pill"><?= e($m['position']) ?></span><?php endif ?></td><td><span class="badge"><?= $m['is_active'] ? e(t('Active')) : e(t('Former')) ?></span></td>
<td class="actions">
  <form method="post" action="<?= e(url('admin/roster/move/' . (int) $m['id'] . '/up')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Up">▲</button></form>
  <form method="post" action="<?= e(url('admin/roster/move/' . (int) $m['id'] . '/down')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Down">▼</button></form>
  <form method="post" action="<?= e(url('admin/roster/toggle/' . (int) $m['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e($m['is_active'] ? t('Make former') : t('Reactivate')) ?></button></form>
  <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/roster/' . (int) $m['id'])) ?>"><?= e(t('Edit')) ?></a>
  <form method="post" action="<?= e(url('admin/roster/delete/' . (int) $m['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this member?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted"><?= e(t('No members yet.')) ?></td></tr><?php endif ?></tbody></table></div></div>
