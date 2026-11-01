<?php /** @var array $rows @var array $registry */ ?>
<p class="muted"><?= e(t('Panels appear in the left or right column, or full width above the footer (e.g. the scrolling partners strip). A column with no visible panels disappears automatically. Lower order = higher up.')) ?></p>
<?php foreach (['left' => 'Left column', 'right' => 'Right column', 'above_footer' => 'Above the footer (full width)'] as $pos => $label): ?>
<div class="card"><h2 class="card-title"><?= e(t($label)) ?></h2><div class="card-body">
<?php $n = 0; foreach ($rows as $r): if ($r['position'] !== $pos) continue; $n++; $v = json_decode((string) $r['visibility'], true) ?: []; $known = isset($registry[$r['panel_key']]); ?>
<form class="panel-row" method="post" action="<?= e(url('admin/panels/save/' . (int) $r['id'])) ?>"><?= csrf_field() ?>
  <input class="input" name="title" value="<?= e($r['title']) ?>" aria-label="Title" maxlength="120">
  <code title="panel"><?= e($r['panel_key']) ?><?= $known ? '' : ' ⚠ missing' ?></code>
  <select class="select" name="position" aria-label="Position"><option value="left" <?= $pos === 'left' ? 'selected' : '' ?>><?= e(t('Left')) ?></option><option value="right" <?= $pos === 'right' ? 'selected' : '' ?>><?= e(t('Right')) ?></option><option value="above_footer" <?= $pos === 'above_footer' ? 'selected' : '' ?>><?= e(t('Above footer')) ?></option></select>
  <input class="input narrow" type="number" name="sort_order" value="<?= (int) $r['sort_order'] ?>" aria-label="Order">
  <select class="select" name="who" aria-label="Visible to"><?php foreach (['all' => 'Everyone', 'guests' => 'Guests', 'members' => 'Members', 'staff' => 'Staff'] as $k => $l): ?><option value="<?= $k ?>" <?= ($v['who'] ?? 'all') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select>
  <label class="checkbox"><input type="checkbox" name="mobile" value="1" <?= ($v['mobile'] ?? true) ? 'checked' : '' ?>> <?= e(t('Mobile')) ?></label>
  <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= $r['enabled'] ? 'checked' : '' ?>> <?= e(t('On')) ?></label>
  <button class="btn btn-sm btn-primary"><?= e(t('Save')) ?></button>
  <?php if ($known && !empty($registry[$r['panel_key']]['meta']['settings'])): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/panels/' . (int) $r['id'])) ?>"><?= e(t('Settings')) ?></a><?php endif ?>
  <button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/panels/delete/' . (int) $r['id'])) ?>" data-confirm="<?= e(t('Remove this panel?')) ?>">✕</button>
</form>
<?php endforeach; if (!$n): ?><p class="muted"><?= e(t('No panels in this column.')) ?></p><?php endif ?>
</div></div>
<?php endforeach ?>
<form class="card" method="post" action="<?= e(url('admin/panels/add')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Add panel')) ?></h2><div class="card-body inline-form">
  <select class="select" name="panel_key"><?php foreach ($registry as $k => $reg): ?><option value="<?= e($k) ?>"><?= e($reg['meta']['title']) ?><?= empty($reg['meta']['multiple']) ? '' : ' (' . e(t('multiple')) . ')' ?></option><?php endforeach ?></select>
  <select class="select" name="position"><option value="left"><?= e(t('Left')) ?></option><option value="right"><?= e(t('Right')) ?></option><option value="above_footer"><?= e(t('Above footer')) ?></option></select>
  <button class="btn btn-primary"><?= e(t('Add')) ?></button></div></form>
