<?php /** @var array $layout */ $u = user(); ?>
<header class="site-header">
<div class="container header-inner">
  <a class="brand" href="<?= e(url('')) ?>"><?php $custom = has_custom_logo(); ?><img class="<?= $custom ? 'brand-logo' : 'brand-mark' ?>" src="<?= e(site_logo()) ?>" alt="<?= $custom && !setting('site.logo_show_name', true) ? e(setting('site.name', 'MagicCMS')) : '' ?>" height="40"<?= $custom ? '' : ' width="40"' ?>><?php if (!$custom || setting('site.logo_show_name', true)): ?><span class="brand-text"><?= e(setting('site.name', 'MagicCMS')) ?></span><?php endif ?></a>
  <?= render('nav', ['items' => MagicCMS\Core\Nav::tree('main'), 'class' => 'site-nav']) ?>
  <div class="header-actions">
    <?php if ($layout['left']): ?><button class="icon-btn drawer-toggle" data-drawer="left" aria-label="<?= e(t('Open left panels')) ?>" aria-controls="drawer-left">◧</button><?php endif ?>
    <?php if ($layout['right']): ?><button class="icon-btn drawer-toggle" data-drawer="right" aria-label="<?= e(t('Open right panels')) ?>" aria-controls="drawer-right">◨</button><?php endif ?>
    <?php if (setting('appearance.allow_user_mode', true)): ?><button class="icon-btn mode-toggle" aria-label="<?= e(t('Switch light/dark mode')) ?>" title="<?= e(t('Switch light/dark mode')) ?>"><span class="mode-icon" aria-hidden="true"></span></button><?php endif ?>
    <?php if ($u): ?>
      <a class="header-user" href="<?= e(url('profile')) ?>"><img class="avatar avatar-sm" src="<?= e(avatar_url($u, 32)) ?>" alt="" width="32" height="32"><span><?= e($u['username']) ?></span></a>
      <?php if (can('admin.access')): ?><a class="btn btn-sm btn-ghost hide-mobile" href="<?= e(url('admin')) ?>"><?= e(t('Admin')) ?></a><?php endif ?>
    <?php else: ?>
      <a class="btn btn-sm btn-ghost" href="<?= e(url('login')) ?>"><?= e(t('Login')) ?></a>
      <?php if (setting('auth.registration', 'open') !== 'closed'): ?><a class="btn btn-sm btn-primary hide-mobile" href="<?= e(url('register')) ?>"><?= e(t('Register')) ?></a><?php endif ?>
    <?php endif ?>
  </div>
</div>
</header>
