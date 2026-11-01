<?php /** @var string $token */ ?>
<div class="auth-card card"><h1 class="card-title"><?= e(t('Choose a new password')) ?></h1>
  <form class="card-body" method="post" action="<?= e(url('reset/' . $token)) ?>"><?= csrf_field() ?>
    <div class="form-row"><label class="label" for="rp1"><?= e(t('New password')) ?></label><input class="input" id="rp1" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required autofocus><small class="help"><?= e(t('8–72 characters.')) ?></small></div>
    <div class="form-row"><label class="label" for="rp2"><?= e(t('Repeat password')) ?></label><input class="input" id="rp2" type="password" name="password2" minlength="8" maxlength="72" autocomplete="new-password" required></div>
    <button class="btn btn-primary btn-block"><?= e(t('Change password')) ?></button>
  </form></div>
