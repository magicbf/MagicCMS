<?php $u = user(); ?>
<section class="hero-lite">
  <div class="hero-lite-inner">
    <p class="eyebrow"><?= e(setting('site.tagline', '')) ?></p>
    <h1><?= e(t('Welcome to')) ?> <span class="grad-text"><?= e(setting('site.name', 'MagicCMS')) ?></span></h1>
    <p class="lead"><?= e(setting('site.description', '')) ?></p>
    <div class="hero-cta">
      <?php if (!$u && setting('auth.registration', 'open') !== 'closed'): ?><a class="btn btn-primary" href="<?= e(url('register')) ?>"><?= e(t('Join the team')) ?></a><?php endif ?>
      <a class="btn btn-ghost" href="<?= e(url('members')) ?>"><?= e(t('Meet the members')) ?></a>
    </div>
  </div>
</section>
<?php if (can('admin.access')): ?>
<div class="card"><h2 class="card-title"><?= e(t('Getting started')) ?></h2><div class="card-body">
  <ul class="checklist">
    <li><a href="<?= e(url('admin/appearance')) ?>"><?= e(t('Pick a colour scheme & light/dark default')) ?></a></li>
    <li><a href="<?= e(url('admin/navigation')) ?>"><?= e(t('Add buttons to the menu')) ?></a></li>
    <li><a href="<?= e(url('admin/panels')) ?>"><?= e(t('Arrange side panels (left / right / none)')) ?></a></li>
    <li><a href="<?= e(url('admin/pages')) ?>"><?= e(t('Create custom pages')) ?></a></li>
  </ul></div></div>
<?php endif ?>
