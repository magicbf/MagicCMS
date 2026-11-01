<?php /** @var ?array $user */ ?>
<?php if ($user): ?>
<div class="userbox">
  <img class="avatar avatar-xl" src="<?= e(avatar_url($user, 128)) ?>" alt="" width="80" height="80">
  <strong class="role-<?= e($user['role_slug']) ?>"><?= e($user['username']) ?></strong>
  <span class="muted"><?= e($user['role_name']) ?></span>
  <div class="userbox-links">
    <a class="btn btn-ghost btn-sm" href="<?= e(url('profile')) ?>"><?= e(t('Profile')) ?></a>
    <?php if (can('admin.access')): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('admin')) ?>"><?= e(t('Admin')) ?></a><?php endif ?>
    <form method="post" action="<?= e(url('logout')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary"><?= e(t('Logout')) ?></button></form>
  </div>
</div>
<?php else: ?>
<form class="panel-login" method="post" action="<?= e(url('login')) ?>"><?= csrf_field() ?>
  <input class="input" name="login" placeholder="<?= e(t('Username or email')) ?>" autocomplete="username" aria-label="<?= e(t('Username or email')) ?>" required>
  <input class="input" type="password" name="password" placeholder="<?= e(t('Password')) ?>" autocomplete="current-password" aria-label="<?= e(t('Password')) ?>" required>
  <label class="checkbox"><input type="checkbox" name="remember" value="1"> <?= e(t('Remember me')) ?></label>
  <button class="btn btn-primary btn-block"><?= e(t('Login')) ?></button>
  <?php if (setting('auth.registration', 'open') !== 'closed'): ?><a class="btn btn-ghost btn-block" href="<?= e(url('register')) ?>"><?= e(t('Register')) ?></a><?php endif ?>
</form>
<?php endif ?>
