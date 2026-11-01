<?php /** @var array $menu @var ?array $item @var array $tops @var array $pages @var array $targets */
$i = $item ?? ['id' => 0, 'parent_id' => 0, 'label' => '', 'icon' => '', 'target_type' => 'module', 'target_ref' => '', 'new_tab' => 0, 'badge' => '', 'color' => '', 'visibility' => '{}'];
$v = json_decode((string) $i['visibility'], true) ?: []; ?>
<form class="card nav-form" method="post" action="<?= e(url('admin/navigation/save?menu=' . $menu['menu_key'])) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $i['id'] ?>">
<div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Label')) ?></label><input class="input" name="label" maxlength="80" value="<?= e($i['label']) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Icon (emoji)')) ?></label><input class="input" name="icon" maxlength="8" value="<?= e($i['icon']) ?>" placeholder="🏆"></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Links to')) ?></label><select class="select" name="target_type" data-ref-switch>
      <?php foreach (['module' => 'Built-in page / module', 'page' => 'Custom page', 'url' => 'External or custom URL', 'action' => 'Login / Profile (smart button)'] as $k => $l): ?><option value="<?= $k ?>" <?= $i['target_type'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row" data-ref="module"><label class="label"><?= e(t('Module')) ?></label><select class="select" name="ref_module"><?php foreach ($targets as $k => $tg): ?><option value="<?= e($k) ?>" <?= $i['target_type'] === 'module' && $i['target_ref'] === $k ? 'selected' : '' ?>><?= e($tg[0]) ?></option><?php endforeach ?></select></div>
    <div class="form-row" data-ref="page"><label class="label"><?= e(t('Page')) ?></label><select class="select" name="ref_page"><?php foreach ($pages as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $i['target_type'] === 'page' && (int) $i['target_ref'] === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach ?></select></div>
    <div class="form-row" data-ref="url"><label class="label"><?= e(t('URL')) ?></label><input class="input" name="ref_url" maxlength="500" value="<?= e($i['target_type'] === 'url' ? $i['target_ref'] : '') ?>" placeholder="https://… or /path"></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Dropdown under')) ?></label><select class="select" name="parent_id"><option value="0">— <?= e(t('top level')) ?> —</option><?php foreach ($tops as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $i['parent_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['label']) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Visible to')) ?></label><select class="select" name="who"><?php foreach (['all' => 'Everyone', 'guests' => 'Guests only', 'members' => 'Members only', 'staff' => 'Staff only'] as $k => $l): ?><option value="<?= $k ?>" <?= ($v['who'] ?? 'all') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Badge (optional)')) ?></label><input class="input" name="badge" maxlength="16" value="<?= e($i['badge']) ?>" placeholder="NEW"></div>
    <div class="form-row"><label class="label"><?= e(t('Button colour override (optional)')) ?></label><input class="input" name="color" maxlength="7" pattern="#[0-9a-fA-F]{6}" value="<?= e($i['color']) ?>" placeholder="#e10613"></div>
  </div>
  <label class="checkbox"><input type="checkbox" name="new_tab" value="1" <?= $i['new_tab'] ? 'checked' : '' ?>> <?= e(t('Open in new tab')) ?></label>
  <label class="checkbox"><input type="checkbox" name="mobile" value="1" <?= ($v['mobile'] ?? true) ? 'checked' : '' ?>> <?= e(t('Show on mobile')) ?></label>
  <p><button class="btn btn-primary"><?= e($i['id'] ? t('Save item') : t('Add item')) ?></button></p>
</div></form>
