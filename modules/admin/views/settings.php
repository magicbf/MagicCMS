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
    <div class="form-row"><label class="label"><?= e(t('Side panels (default layout)')) ?></label><select class="select" name="layout_mode"><?php foreach (['right' => 'Right column only (default – wide, modern look)', 'both' => 'Left + right columns', 'left' => 'Left column only', 'none' => 'No panels (full width)'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('layout.mode', 'right') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select><small class="help"><?= e(t('A single right-hand column is the default; the left column is optional. Individual pages can override this.')) ?></small></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Registration')) ?></label><select class="select" name="registration"><?php foreach (['open' => 'Open', 'approval' => 'Requires admin approval', 'closed' => 'Closed'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('auth.registration', 'open') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Registrations per connection per hour (0 = no limit)')) ?></label><input class="input" type="number" min="0" max="1000" name="reg_limit" value="<?= (int) setting('auth.reg_limit', 5) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Max avatar size (MB, 1–5)')) ?></label><input class="input" type="number" min="1" max="5" name="avatar_mb" value="<?= (int) setting('upload.avatar_max_mb', 5) ?>"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('"Online" window (minutes)')) ?></label><input class="input" type="number" min="1" max="60" name="online_min" value="<?= (int) setting('online.window_min', 5) ?>"></div>
  <label class="checkbox"><input type="checkbox" name="email_verify" value="1" <?= setting('auth.email_verify') ? 'checked' : '' ?>> <?= e(t('Require e-mail verification before members can log in')) ?></label>
  <?php $unv = (int) db()->val('SELECT COUNT(*) FROM {users} WHERE email_verified_at IS NULL'); if ($unv): ?><small class="help"><?= e(t('{n} existing account(s) are unverified and would have to confirm their address first (you can mark them verified in Admin → Users).', ['n' => $unv])) ?></small><?php endif ?>
  <label class="checkbox"><input type="checkbox" name="show_name" value="1" <?= show_site_name() ? 'checked' : '' ?>> <?= e(t('Show the site name next to the logo in the header (untick for a logo-only header)')) ?></label>
  <label class="checkbox"><input type="checkbox" name="maintenance" value="1" <?= setting('site.maintenance') ? 'checked' : '' ?>> <?= e(t('Maintenance mode (only staff can see the site)')) ?></label>
  <h3 class="section-title"><?= e(t('Header & layout style')) ?></h3>
  <p class="muted"><?= e(t('Rearranges logo, menu and account area. Works with every theme – the theme still decides colours, shapes and fonts.')) ?></p>
  <div class="hs-picker" role="radiogroup" aria-label="<?= e(t('Header style')) ?>">
  <?php foreach (MagicCMS\Core\Layout::HEADERS as $k => $label): [$name, $desc] = array_pad(explode(' – ', $label, 2), 2, ''); ?>
    <label class="hs-opt"><input type="radio" name="layout_header" value="<?= e($k) ?>" <?= MagicCMS\Core\Layout::header() === $k ? 'checked' : '' ?>>
      <span class="hs-mini hs-<?= e($k) ?>" aria-hidden="true"><i class="lg"></i><i class="mn"></i><i class="ac"></i><i class="bd"></i></span>
      <b><?= e(t($name)) ?></b><small><?= e(t($desc)) ?></small></label>
  <?php endforeach ?>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Rhythm')) ?></label><select class="select" name="layout_rhythm"><?php foreach (MagicCMS\Core\Layout::RHYTHMS as $k => $l): ?><option value="<?= e($k) ?>" <?= MagicCMS\Core\Layout::rhythm() === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select><small class="help"><?= e(t('Staggered offsets the sidebars and gives the page a less rigid look (wide screens only).')) ?></small></div>
    <div class="form-row"><label class="label"><?= e(t('Sides')) ?></label><label class="checkbox"><input type="checkbox" name="layout_mirror" value="1" <?= setting('layout.mirror') ? 'checked' : '' ?>> <?= e(t('Mirror the side panels (left ↔ right)')) ?></label></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Login / account area shape')) ?></label><select class="select" name="layout_acct_shape"><?php foreach (MagicCMS\Core\Layout::ACCT_SHAPES as $k => $l): ?><option value="<?= e($k) ?>" <?= MagicCMS\Core\Layout::acctShape() === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select><small class="help"><?= e(t('Applies to the login, register and account buttons in every header style.')) ?></small></div>
    <div class="form-row"><label class="label"><?= e(t('Top bar options')) ?></label>
      <label class="checkbox"><input type="checkbox" name="layout_topbar_sticky" value="1" <?= setting('layout.topbar_sticky', true) ? 'checked' : '' ?>> <?= e(t('Keep the bar fixed to the top while scrolling')) ?></label>
      <select class="select" name="layout_brand_align" aria-label="<?= e(t('Logo alignment')) ?>"><?php foreach (MagicCMS\Core\Layout::BRAND_ALIGN as $k => $l): ?><option value="<?= e($k) ?>" <?= MagicCMS\Core\Layout::brandAlign() === $k ? 'selected' : '' ?>><?= e(t('Logo: ' . $l)) ?></option><?php endforeach ?></select>
      <small class="help"><?= e(t('These apply to the “Top bar” header style, which shows at most 6 menu buttons – any further items go into a “More” dropdown.')) ?></small></div>
  </div>
  <p><button class="btn btn-primary"><?= e(t('Save settings')) ?></button></p>
</div></form>

<div class="card"><h2 class="card-title"><?= e(t('Logo')) ?></h2><div class="card-body">
  <div class="logo-preview"><img id="logo-prev" src="<?= e(site_logo()) ?>" alt="" height="48" style="max-height:56px;width:auto;max-width:260px"></div>
  <?php if (has_custom_logo()): ?><div class="alert alert-warning" id="logo-warn" hidden>⚠ <?= e(t("The uploaded logo was saved but your server does not deliver it (the browser cannot load {url}). Check that the uploads/ folder is reachable from the web and that its .htaccess is accepted by the server; until then the default logo is shown.", ["url" => site_logo()])) ?></div><?php endif ?>
  <form method="post" action="<?= e(url('admin/settings/logo')) ?>" enctype="multipart/form-data" class="mt"><?= csrf_field() ?>
    <div class="form-row"><label class="label" for="logo"><?= e(t('Replace logo')) ?></label>
    <input class="input" id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" required>
    <small class="help"><?= e(t('PNG, JPG, WebP, GIF or a clean SVG, up to 2 MB. Raster images are resized to fit 640×200. A transparent PNG/SVG about 40 px tall works best. It also becomes the browser tab icon.')) ?></small></div>
    <button class="btn btn-primary"><?= e(t('Upload logo')) ?></button>
  </form>
  <?php if (has_custom_logo()): ?><form method="post" action="<?= e(url('admin/settings/logo/reset')) ?>" class="mt"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" data-confirm="<?= e(t('Restore the default logo?')) ?>"><?= e(t('Restore default logo')) ?></button></form><?php endif ?>
</div></div>

<?php $mc = MagicCMS\Core\Mailer::config(); ?>
<div class="card"><h2 class="card-title"><?= e(t('E-mail')) ?></h2>
<form class="card-body" method="post" action="<?= e(url('admin/settings/mail')) ?>" autocomplete="off"><?= csrf_field() ?>
  <p class="muted"><?= e(t('Used for e-mail verification, password reset and private-message notifications.')) ?> <?php if ($mc['driver'] === 'log'): ?><strong class="role-admin"><?= e(t('Log mode: messages are only written to cache/mail.log, nothing is delivered.')) ?></strong><?php endif ?></p>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Delivery')) ?></label><select class="select" name="driver"><?php foreach (['mail' => 'PHP mail() – simplest', 'smtp' => 'SMTP server – most reliable', 'log' => 'Log only (testing)'] as $k => $l): ?><option value="<?= $k ?>" <?= $mc['driver'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('From name')) ?></label><input class="input" name="from_name" maxlength="80" value="<?= e($mc['from_name']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('From address')) ?></label><input class="input" type="email" name="from_email" maxlength="190" value="<?= e($mc['from_email']) ?>" placeholder="<?= e(MagicCMS\Core\Mailer::fromEmail()) ?>"></div>
  </div>
  <details <?= $mc['driver'] === 'smtp' ? 'open' : '' ?>><summary class="muted"><?= e(t('SMTP settings')) ?></summary><div class="form-grid mt">
    <div class="form-row"><label class="label"><?= e(t('Host')) ?></label><input class="input" name="smtp_host" maxlength="190" value="<?= e($mc['host']) ?>" placeholder="smtp.example.com"></div>
    <div class="form-row"><label class="label"><?= e(t('Port')) ?></label><input class="input" type="number" name="smtp_port" value="<?= (int) $mc['port'] ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Encryption')) ?></label><select class="select" name="smtp_secure"><?php foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL/TLS (465)', 'none' => 'None (25)'] as $k => $l): ?><option value="<?= $k ?>" <?= $mc['secure'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Username')) ?></label><input class="input" name="smtp_user" maxlength="190" value="<?= e($mc['user']) ?>" autocomplete="off"></div>
    <div class="form-row"><label class="label"><?= e(t('Password')) ?></label><input class="input" type="password" name="smtp_pass" autocomplete="new-password" placeholder="<?= $mc['pass'] !== '' ? '•••••••• (' . e(t('stored encrypted – leave empty to keep')) . ')' : '' ?>"></div>
  </div></details>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Save e-mail settings')) ?></button>
  <button class="btn btn-ghost" formaction="<?= e(url('admin/settings/mail/test')) ?>"><?= e(t('Send a test e-mail to me')) ?></button></p>
</form></div>
