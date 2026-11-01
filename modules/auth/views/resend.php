<div class="auth-card card"><h1 class="card-title"><?= e(t('Resend confirmation')) ?></h1>
  <form class="card-body" method="post" action="<?= e(url('verify/resend')) ?>"><?= csrf_field() ?>
    <p class="muted"><?= e(t('Enter your username or e-mail address and we will send a new confirmation link if the account still needs one.')) ?></p>
    <div class="form-row"><label class="label" for="rs"><?= e(t('Username or email')) ?></label><input class="input" id="rs" name="login" required autofocus></div>
    <button class="btn btn-primary btn-block"><?= e(t('Send link')) ?></button>
    <p class="muted center"><a href="<?= e(url('login')) ?>"><?= e(t('Back to login')) ?></a></p>
  </form></div>
