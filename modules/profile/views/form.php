<?php /** @var array $u */ $max = max(1, min(5, (int) setting('upload.avatar_max_mb', 5))); ?>
<div class="card"><h1 class="card-title"><?= e(t('Avatar')) ?></h1>
<div class="card-body profile-head">
  <img class="avatar avatar-xl" src="<?= e(avatar_url($u, 256)) ?>" alt="" width="128" height="128">
  <div>
    <form method="post" action="<?= e(url('profile/avatar')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= $max * 1048576 ?>">
      <div class="form-row"><label class="label" for="av"><?= e(t('Upload image')) ?></label><input class="input" id="av" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" required>
      <small class="help"><?= e(t('JPG, PNG, GIF or WebP, up to {mb} MB. It is cropped to a square and resized automatically.', ['mb' => $max])) ?></small></div>
      <button class="btn btn-primary"><?= e(t('Upload')) ?></button>
    </form>
    <?php if ($u['avatar']): ?><form method="post" action="<?= e(url('profile/avatar/delete')) ?>" class="mt"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= e(t('Remove avatar')) ?></button></form><?php endif ?>
  </div>
</div></div>

<div class="card"><h2 class="card-title"><?= e(t('Profile')) ?></h2>
<form class="card-body" method="post" action="<?= e(url('profile')) ?>"><?= csrf_field() ?>
  <div class="form-row"><label class="label"><?= e(t('Username')) ?></label><input class="input" value="<?= e($u['username']) ?>" disabled></div>
  <div class="form-row"><label class="label" for="em"><?= e(t('Email')) ?></label><input class="input" id="em" type="email" name="email" value="<?= e($u['email']) ?>" required></div>
  <div class="form-row"><label class="label" for="bio"><?= e(t('About me')) ?></label><textarea class="textarea" id="bio" name="bio" rows="3" maxlength="500"><?= e($u['bio']) ?></textarea></div>
  <div class="form-row"><label class="label" for="sig"><?= e(t('Signature')) ?></label><input class="input" id="sig" name="signature" maxlength="300" value="<?= e($u['signature']) ?>"></div>
  <div class="form-grid">
    <div class="form-row"><label class="label" for="tz"><?= e(t('Timezone')) ?></label><select class="select" id="tz" name="timezone"><option value=""><?= e(t('Site default')) ?></option><?php foreach (DateTimeZone::listIdentifiers() as $tz): ?><option <?= ($u['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label" for="tm"><?= e(t('Appearance')) ?></label><select class="select" id="tm" name="theme_mode"><option value=""><?= e(t('Site default')) ?></option><?php foreach (['dark' => 'Dark', 'light' => 'Light', 'auto' => 'Follow my device'] as $k => $l): ?><option value="<?= $k ?>" <?= ($u['theme_mode'] ?? '') === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
  </div>
  <label class="checkbox"><input type="checkbox" name="notify_pm" value="1" <?= !empty($u['notify_pm']) ? 'checked' : '' ?>> <?= e(t('E-mail me when I receive a private message')) ?></label>
  <button class="btn btn-primary"><?= e(t('Save')) ?></button>
</form></div>

<div class="card"><h2 class="card-title"><?= e(t('Change password')) ?></h2>
<form class="card-body" method="post" action="<?= e(url('profile/password')) ?>"><?= csrf_field() ?>
  <div class="form-row"><label class="label" for="cp"><?= e(t('Current password')) ?></label><input class="input" id="cp" type="password" name="current" autocomplete="current-password" required></div>
  <div class="form-grid">
    <div class="form-row"><label class="label" for="np"><?= e(t('New password')) ?></label><input class="input" id="np" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required></div>
    <div class="form-row"><label class="label" for="np2"><?= e(t('Repeat')) ?></label><input class="input" id="np2" type="password" name="password2" minlength="8" maxlength="72" autocomplete="new-password" required></div>
  </div>
  <button class="btn btn-primary"><?= e(t('Change password')) ?></button>
</form></div>
