<form class="card" method="post" action="<?= e(url('admin/settings')) ?>"><?= csrf_field() ?>
<h2 class="card-title"><?= e(t('General')) ?></h2>
<div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Site name')) ?></label><input class="input" name="site_name" maxlength="60" value="<?= e(setting('site.name')) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Tagline')) ?></label><input class="input" name="site_tagline" maxlength="120" value="<?= e(setting('site.tagline')) ?>"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Description (SEO)')) ?></label><input class="input" name="site_description" maxlength="300" value="<?= e(setting('site.description')) ?>"></div>
  <div class="form-row"><label class="label"><?= e(t('Footer text')) ?></label><input class="input" name="site_footer" maxlength="200" value="<?= e(setting('site.footer')) ?>"><small class="help">{year} = <?= e(gmdate('Y')) ?></small></div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Timezone')) ?></label><select class="select" name="timezone"><?php foreach (DateTimeZone::listIdentifiers() as $tz): ?><option <?= setting('site.timezone', 'UTC') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Side panels (default layout)')) ?></label><select class="select" name="layout_mode"><?php foreach (['both' => 'Left + right', 'left' => 'Left only', 'right' => 'Right only', 'none' => 'No panels (full width)'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('layout.mode', 'both') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select><small class="help"><?= e(t('Individual pages can override this.')) ?></small></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Registration')) ?></label><select class="select" name="registration"><?php foreach (['open' => 'Open', 'approval' => 'Requires admin approval', 'closed' => 'Closed'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('auth.registration', 'open') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Max avatar size (MB, 1–5)')) ?></label><input class="input" type="number" min="1" max="5" name="avatar_mb" value="<?= (int) setting('upload.avatar_max_mb', 5) ?>"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('"Online" window (minutes)')) ?></label><input class="input" type="number" min="1" max="60" name="online_min" value="<?= (int) setting('online.window_min', 5) ?>"></div>
  <label class="checkbox"><input type="checkbox" name="maintenance" value="1" <?= setting('site.maintenance') ? 'checked' : '' ?>> <?= e(t('Maintenance mode (only staff can see the site)')) ?></label>
  <p><button class="btn btn-primary"><?= e(t('Save settings')) ?></button></p>
</div></form>
