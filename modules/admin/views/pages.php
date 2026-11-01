<?php /** @var array $rows */ ?>
<p><a class="btn btn-primary" href="<?= e(url('admin/pages/new')) ?>">+ <?= e(t('New page')) ?></a></p>
<div class="card"><div class="card-body table-wrap"><table class="table"><thead><tr><th><?= e(t('Title')) ?></th><th>URL</th><th><?= e(t('Status')) ?></th><th><?= e(t('Updated')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['title']) ?></strong></td><td><a href="<?= e(url('p/' . $r['slug'])) ?>">/p/<?= e($r['slug']) ?></a></td><td><span class="badge"><?= $r['published'] ? e(t('Published')) : e(t('Draft')) ?></span></td><td><?= e(fmt_date($r['updated_at'])) ?></td>
<td class="actions"><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/pages/' . (int) $r['id'])) ?>"><?= e(t('Edit')) ?></a>
<form method="post" action="<?= e(url('admin/pages/delete/' . (int) $r['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this page?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted"><?= e(t('No pages yet.')) ?></td></tr><?php endif ?></tbody></table></div></div>
