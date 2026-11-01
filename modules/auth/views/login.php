<?php /** @var string $next @var string $old @var bool $verify */ ?>
<div class="auth-card card">
  <h1 class="card-title"><?= e(t('Login')) ?></h1>
  <form class="card-body" method="post" action="<?= e(url('login')) ?>">
    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
    <div class="form-row"><label class="label" for="login"><?= e(t('Username or email')) ?></label><input class="input" id="login" name="login" value="<?= e($old) ?>" autocomplete="username" required autofocus></div>
    <div class="form-row"><label class="label" for="pw"><?= e(t('Password')) ?></label><input class="input" id="pw" type="password" name="password" autocomplete="current-password" required></div>
    <label class="checkbox"><input type="checkbox" name="remember" value="1"> <?= e(t('Keep me logged in')) ?></label>
    <button class="btn btn-primary btn-block"><?= e(t('Login')) ?></button>
    <p class="muted center small"><a href="<?= e(url('forgot')) ?>"><?= e(t('Forgot your password?')) ?></a><?php if (!empty($verify)): ?> · <a href="<?= e(url('verify/resend')) ?>"><?= e(t('Resend confirmation e-mail')) ?></a><?php endif ?></p>
    <?php if (setting('auth.registration', 'open') !== 'closed'): ?><p class="muted center"><?= e(t('No account yet?')) ?> <a href="<?= e(url('register')) ?>"><?= e(t('Register')) ?></a></p><?php endif ?>
  </form>
</div>
