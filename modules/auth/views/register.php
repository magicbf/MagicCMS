<?php /** @var array $f @var string $mode */ ?>
<div class="auth-card card">
  <h1 class="card-title"><?= e(t('Create account')) ?></h1>
  <?php if ($mode === 'closed'): ?><div class="card-body"><p><?= e(t('Registration is currently closed.')) ?></p></div><?php else: ?>
  <form class="card-body" method="post" action="<?= e(url('register')) ?>">
    <?= csrf_field() ?>
    <div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
    <?php if ($mode === 'approval'): ?><div class="alert alert-info"><?= e(t('New accounts must be approved by an administrator.')) ?></div><?php endif ?>
    <div class="form-row"><label class="label" for="u"><?= e(t('Username')) ?></label><input class="input" id="u" name="username" value="<?= e($f['username'] ?? '') ?>" pattern="[A-Za-z0-9_\-]{3,20}" autocomplete="username" required></div>
    <div class="form-row"><label class="label" for="e"><?= e(t('Email')) ?></label><input class="input" id="e" type="email" name="email" value="<?= e($f['email'] ?? '') ?>" autocomplete="email" required></div>
    <div class="form-row"><label class="label" for="p1"><?= e(t('Password')) ?></label><input class="input" id="p1" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required><small class="help"><?= e(t('8–72 characters.')) ?></small></div>
    <div class="form-row"><label class="label" for="p2"><?= e(t('Repeat password')) ?></label><input class="input" id="p2" type="password" name="password2" minlength="8" maxlength="72" autocomplete="new-password" required></div>
    <label class="checkbox"><input type="checkbox" name="tos" value="1" required> <?= e(t('I agree to follow the site rules.')) ?></label>
    <button class="btn btn-primary btn-block"><?= e(t('Register')) ?></button>
    <p class="muted center"><?= e(t('Already registered?')) ?> <a href="<?= e(url('login')) ?>"><?= e(t('Login')) ?></a></p>
  </form><?php endif ?>
</div>
