<div class="auth-card card"><h1 class="card-title"><?= e(t('Forgot password')) ?></h1>
  <form class="card-body" method="post" action="<?= e(url('forgot')) ?>"><?= csrf_field() ?>
    <p class="muted"><?= e(t('Enter your username or e-mail address. If an account matches, we will send you a link to choose a new password.')) ?></p>
    <div class="form-row"><label class="label" for="fl"><?= e(t('Username or email')) ?></label><input class="input" id="fl" name="login" autocomplete="username" required autofocus></div>
    <button class="btn btn-primary btn-block"><?= e(t('Send reset link')) ?></button>
    <p class="muted center"><a href="<?= e(url('login')) ?>"><?= e(t('Back to login')) ?></a></p>
  </form></div>
