<?php /** @var array $rows @var ?array $panel */ ?>
<p><a class="btn btn-primary" href="<?= e(url('admin/partners/new')) ?>">+ <?= e(t('New partner')) ?></a>
  <a class="btn btn-ghost" href="<?= e(url('admin/panels')) ?>"><?= e(t('Show / hide / move the strip: Admin → Panels → “Above the footer”')) ?></a></p>
<?php if ($panel && !$panel['enabled']): ?><div class="alert alert-warning"><?= e(t('The partners strip is switched off in Admin → Panels.')) ?></div><?php endif ?>
<div class="card"><div class="card-body table-wrap"><table class="table"><thead><tr><th></th><th><?= e(t('Partner')) ?></th><th><?= e(t('Link')) ?></th><th><?= e(t('Status')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $img = upload_url('partners', $r['logo']); ?><tr class="<?= $r['enabled'] ? '' : 'is-off' ?>">
<td><?php if ($img): ?><img src="<?= e($img) ?>" alt="" style="height:36px;max-width:120px;object-fit:contain;background:rgba(255,255,255,.06);padding:4px;border-radius:6px"><?php endif ?></td>
<td><strong><?= e($r['name']) ?></strong></td><td class="muted small"><?= e($r['url'] ?: '—') ?></td><td><span class="badge"><?= $r['enabled'] ? e(t('Shown')) : e(t('Hidden')) ?></span></td>
<td class="actions">
  <form method="post" action="<?= e(url('admin/partners/move/' . (int) $r['id'] . '/up')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Up">▲</button></form>
  <form method="post" action="<?= e(url('admin/partners/move/' . (int) $r['id'] . '/down')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Down">▼</button></form>
  <form method="post" action="<?= e(url('admin/partners/toggle/' . (int) $r['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e($r['enabled'] ? t('Hide') : t('Show')) ?></button></form>
  <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/partners/' . (int) $r['id'])) ?>"><?= e(t('Edit')) ?></a>
  <form method="post" action="<?= e(url('admin/partners/delete/' . (int) $r['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Remove this partner?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted"><?= e(t('No partners yet. Add logos – they scroll through the strip above the footer. With no logos the strip is not shown.')) ?></td></tr><?php endif ?></tbody></table></div></div>
