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
  <label class="checkbox"><input type="checkbox" name="logo_show_name" value="1" <?= setting('site.logo_show_name', true) ? 'checked' : '' ?>> <?= e(t('Show the site name next to a custom logo')) ?></label>
  <label class="checkbox"><input type="checkbox" name="maintenance" value="1" <?= setting('site.maintenance') ? 'checked' : '' ?>> <?= e(t('Maintenance mode (only staff can see the site)')) ?></label>
  <p><button class="btn btn-primary"><?= e(t('Save settings')) ?></button></p>
</div></form>

<div class="card"><h2 class="card-title"><?= e(t('Logo')) ?></h2><div class="card-body">
  <div class="logo-preview"><img src="<?= e(site_logo()) ?>" alt="" height="48" style="max-height:56px;width:auto;max-width:260px"></div>
  <form method="post" action="<?= e(url('admin/settings/logo')) ?>" enctype="multipart/form-data" class="mt"><?= csrf_field() ?>
    <div class="form-row"><label class="label" for="logo"><?= e(t('Replace logo')) ?></label>
    <input class="input" id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" required>
    <small class="help"><?= e(t('PNG, JPG, WebP, GIF or a clean SVG, up to 2 MB. Raster images are resized to fit 640×200. A transparent PNG/SVG about 40 px tall works best. It also becomes the browser tab icon.')) ?></small></div>
    <button class="btn btn-primary"><?= e(t('Upload logo')) ?></button>
  </form>
  <?php if (has_custom_logo()): ?><form method="post" action="<?= e(url('admin/settings/logo/reset')) ?>" class="mt"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" data-confirm="<?= e(t('Restore the default logo?')) ?>"><?= e(t('Restore default logo')) ?></button></form><?php endif ?>
</div></div>
