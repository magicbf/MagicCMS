<?php /** @var array $colors @var array $warn @var array $themes */
use MagicCMS\Core\Appearance as A;
$presets = []; foreach (A::PRESETS as $k => [$n, $c]) { $presets[$k] = $c; } ?>
<?php foreach ($warn as $w): ?><div class="alert alert-warning">⚠ <?= e($w) ?></div><?php endforeach ?>
<form class="card" method="post" action="<?= e(url('admin/appearance')) ?>" data-presets="<?= e(json_encode($presets)) ?>"><?= csrf_field() ?>
<h2 class="card-title"><?= e(t('Colour scheme')) ?></h2>
<div class="card-body">
  <div class="swatches" role="radiogroup" aria-label="<?= e(t('Presets')) ?>">
  <?php foreach (A::PRESETS as $k => [$n, $c]): ?>
    <label class="swatch"><input type="radio" name="scheme" value="<?= e($k) ?>" <?= setting('appearance.scheme', 'crimson') === $k ? 'checked' : '' ?>>
      <span class="swatch-chip" style="background:linear-gradient(135deg,<?= e($c['primary_hi']) ?>,<?= e($c['primary']) ?> 55%,<?= e($c['primary_lo']) ?>)"></span><span><?= e($n) ?></span></label>
  <?php endforeach ?>
    <label class="swatch"><input type="radio" name="scheme" value="custom" <?= setting('appearance.scheme') === 'custom' ? 'checked' : '' ?>><span class="swatch-chip swatch-custom">✎</span><span><?= e(t('Custom')) ?></span></label>
  </div>
  <div class="color-grid">
  <?php foreach (A::TOKENS as $k => [$var, $label]): ?>
    <label class="color-pick"><span><?= e(t($label)) ?></span><input type="color" name="c_<?= e($k) ?>" data-token="<?= e($var) ?>" value="<?= e($colors[$k]) ?>"></label>
  <?php endforeach ?>
  </div>
  <small class="help"><?= e(t('Custom colours are used when "Custom" is selected. Presets preview instantly.')) ?></small>
</div>
<h2 class="card-title"><?= e(t('Mode, shape & motion')) ?></h2>
<div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Default mode')) ?></label><select class="select" name="mode_default"><?php foreach (['dark' => 'Dark', 'light' => 'Light', 'auto' => 'Follow visitor device'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('appearance.mode_default', 'dark') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Corner radius')) ?> (<output id="rv"><?= (int) setting('appearance.radius', 16) ?></output>px)</label><input type="range" name="radius" min="0" max="32" value="<?= (int) setting('appearance.radius', 16) ?>" data-token="--radius" data-unit="px"></div>
  </div>
  <label class="checkbox"><input type="checkbox" name="allow_user_mode" value="1" <?= setting('appearance.allow_user_mode', true) ? 'checked' : '' ?>> <?= e(t('Let users switch between light and dark')) ?></label>
  <div class="form-row mt"><label class="label"><?= e(t('Animations')) ?></label><select class="select" name="motion"><?php foreach (['full' => 'Full', 'subtle' => 'Subtle', 'off' => 'Off'] as $k => $l): ?><option value="<?= $k ?>" <?= A::motion() === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
</div>
<h2 class="card-title"><?= e(t('Navigation buttons')) ?></h2>
<div class="card-body"><div class="form-grid">
  <div class="form-row"><label class="label"><?= e(t('Style')) ?></label><select class="select" name="nav_style"><?php foreach (['gradient' => 'Pill group (active = gradient)', 'flash' => 'Flashy (every button gradient)', 'solid' => 'Solid', 'outline' => 'Outline', 'glass' => 'Glass'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('nav.button_style', 'gradient') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
  <div class="form-row"><label class="label"><?= e(t('Hover effect')) ?></label><select class="select" name="nav_fx"><?php foreach (['shine' => 'Shine sweep', 'glow' => 'Glow', 'lift' => 'Lift', 'none' => 'None'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('nav.hover_fx', 'shine') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
</div></div>
<h2 class="card-title"><?= e(t('Theme')) ?></h2>
<div class="card-body">
  <div class="form-row"><label class="label"><?= e(t('Active theme')) ?></label><select class="select" name="theme"><?php foreach ($themes as $slug => $m): ?><option value="<?= e($slug) ?>" <?= setting('theme.active', 'magic-red') === $slug ? 'selected' : '' ?>><?= e($m['name']) ?> <?= e($m['version'] ?? '') ?></option><?php endforeach ?></select><small class="help"><?= e(t('Install themes by copying their folder into /themes. See docs/THEMING.md.')) ?></small></div>
  <p><button class="btn btn-primary"><?= e(t('Save appearance')) ?></button></p>
</div></form>
