<?php /** @var array $layout */ $u = user(); $custom = has_custom_logo(); $showName = !$custom || setting('site.logo_show_name', true); ?>
<header class="site-header" id="top">
  <div class="container header-top">
    <a class="brand brand-xl" href="<?= e(url('')) ?>">
      <img class="<?= $custom ? 'brand-logo' : 'brand-mark' ?>" src="<?= e(site_logo()) ?>" alt="<?= !$showName ? e(setting('site.name', 'MagicCMS')) : '' ?>" height="64"<?= $custom ? '' : ' width="64"' ?>>
      <?php if ($showName): ?><span class="brand-text"><b class="brand-name"><?= e(setting('site.name', 'MagicCMS')) ?></b><?php if ((string) setting('site.tagline', '') !== ''): ?><small class="brand-sub"><?= e(setting('site.tagline')) ?></small><?php endif ?></span><?php endif ?>
    </a>
    <div class="header-actions">
      <?php if ($layout['left']): ?><button class="icon-btn drawer-toggle" data-drawer="left" aria-label="<?= e(t('Open left panels')) ?>" aria-controls="drawer-left">◧</button><?php endif ?>
      <?php if ($layout['right']): ?><button class="icon-btn drawer-toggle" data-drawer="right" aria-label="<?= e(t('Open right panels')) ?>" aria-controls="drawer-right">◨</button><?php endif ?>
      <?php if ($u): $nn = notif_unread(); ?><a class="icon-btn notif-link" href="<?= e(url('notifications')) ?>" aria-label="<?= e(t('Notifications')) ?><?= $nn ? ' (' . $nn . ')' : '' ?>" title="<?= e(t('Notifications')) ?>">🔔<?php if ($nn): ?><b class="pm-dot"><?= $nn > 9 ? '9+' : $nn ?></b><?php endif ?></a><?php endif ?>
      <?php if (pm_enabled()): $pmn = pm_unread(); ?><a class="icon-btn pm-link" href="<?= e(url('messages')) ?>" aria-label="<?= e(t('Messages')) ?><?= $pmn ? ' (' . $pmn . ')' : '' ?>" title="<?= e(t('Messages')) ?>">✉<?php if ($pmn): ?><b class="pm-dot"><?= $pmn > 9 ? '9+' : $pmn ?></b><?php endif ?></a><?php endif ?>
      <?php if (setting('appearance.allow_user_mode', true)): ?><button class="icon-btn mode-toggle" aria-label="<?= e(t('Switch light/dark mode')) ?>" title="<?= e(t('Switch light/dark mode')) ?>"><span class="mode-icon" aria-hidden="true"></span></button><?php endif ?>
      <?php if ($u): ?>
        <a class="header-user" href="<?= e(url('profile')) ?>"><img class="avatar avatar-md" src="<?= e(avatar_url($u, 64)) ?>" alt="" width="44" height="44"><span class="hu-text"><b><?= e($u['username']) ?></b><small><?= e($u['role_name']) ?></small></span></a>
        <?php if (can('admin.access')): ?><a class="btn btn-sm btn-ghost hide-mobile" href="<?= e(url('admin')) ?>"><?= e(t('Admin')) ?></a><?php endif ?>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= e(url('login')) ?>"><?= e(t('Login')) ?></a>
        <?php if (setting('auth.registration', 'open') !== 'closed'): ?><a class="btn btn-primary hide-mobile" href="<?= e(url('register')) ?>"><?= e(t('Register')) ?></a><?php endif ?>
      <?php endif ?>
    </div>
  </div>
</header>
<div class="nav-sentinel" aria-hidden="true"></div>
<div class="site-navbar">
  <div class="container navbar-inner">
    <a class="nav-mini-brand" href="<?= e(url('')) ?>" aria-label="<?= e(setting('site.name', 'MagicCMS')) ?>"><img src="<?= e(site_logo()) ?>" alt="" height="30"></a>
    <?= render('nav', ['items' => MagicCMS\Core\Nav::tree('main'), 'class' => 'site-nav']) ?>
  </div>
</div>
