<?php /** @var string $n @var array $r @var array $targets @var array $pages */
$type = $r["btn{$n}_type"] ?? 'module'; $ref = $r["btn{$n}_ref"] ?? ''; ?>
<fieldset class="btn-fields"><legend class="label"><?= e(t('Button {n}', ['n' => $n])) ?></legend>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Label')) ?></label><input class="input" name="btn<?= $n ?>_label" maxlength="40" value="<?= e($r["btn{$n}_label"] ?? '') ?>" placeholder="<?= e(t('empty = no button')) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Links to')) ?></label><select class="select" name="btn<?= $n ?>_type" data-ref-switch><?php foreach (['module' => 'Built-in page / module', 'page' => 'Custom page', 'url' => 'URL'] as $k => $l): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row" data-ref="module"><label class="label"><?= e(t('Module')) ?></label><select class="select" name="btn<?= $n ?>_module"><?php foreach ($targets as $k => $tg): ?><option value="<?= e($k) ?>" <?= $type === 'module' && $ref === $k ? 'selected' : '' ?>><?= e($tg[0]) ?></option><?php endforeach ?></select></div>
    <div class="form-row" data-ref="page"><label class="label"><?= e(t('Page')) ?></label><select class="select" name="btn<?= $n ?>_page"><?php foreach ($pages as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $type === 'page' && (int) $ref === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach ?></select></div>
    <div class="form-row" data-ref="url"><label class="label">URL</label><input class="input" name="btn<?= $n ?>_url" maxlength="500" value="<?= e($type === 'url' ? $ref : '') ?>" placeholder="https://… or /path"></div>
  </div>
</fieldset>
