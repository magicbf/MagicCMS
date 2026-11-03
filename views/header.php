<?php /** @var array $layout */ $u = user(); $custom = has_custom_logo(); $showName = show_site_name();
$style = MagicCMS\Core\Layout::header();
$navItems = MagicCMS\Core\Nav::tree('main');
$brandHtml = static function (string $extra = '') use ($custom, $showName): string { ob_start(); ?>
<a class="brand brand-xl<?= $showName ? '' : ' brand-only' ?><?= $extra ? ' ' . e($extra) : '' ?>" href="<?= e(url('')) ?>">
      <img class="<?= $custom ? 'brand-logo' : 'brand-mark' ?>" data-fallback="<?= e(asset('logo-mark.svg')) ?>" src="<?= e(site_logo()) ?>" alt="<?= !$showName ? e(setting('site.name', 'MagicCMS')) : '' ?>" height="64"<?= $custom ? '' : ' width="64"' ?>>
      <?php if ($showName): ?><span class="brand-text"><b class="brand-name"><?= e(setting('site.name', 'MagicCMS')) ?></b><?php if ((string) setting('site.tagline', '') !== '' && setting('site.show_tagline', true)): ?><small class="brand-sub"><?= e(setting('site.tagline')) ?></small><?php endif ?></span><?php endif ?>
    </a>
<?php return (string) ob_get_clean(); };
$actionsHtml = static function () use ($layout, $u): string { ob_start(); ?>
    <div class="header-actions acct-<?= e(MagicCMS\Core\Layout::acctShape()) ?>">
      <?php if ($u): $nn = notif_unread(); ?><a class="icon-btn notif-link" href="<?= e(url('notifications')) ?>" aria-label="<?= e(t('Notifications')) ?><?= $nn ? ' (' . $nn . ')' : '' ?>" title="<?= e(t('Notifications')) ?>">🔔<?php if ($nn): ?><b class="pm-dot"><?= $nn > 9 ? '9+' : $nn ?></b><?php endif ?></a><?php endif ?>
      <?php if (pm_enabled()): $pmn = pm_unread(); ?><a class="icon-btn pm-link" href="<?= e(url('messages')) ?>" aria-label="<?= e(t('Messages')) ?><?= $pmn ? ' (' . $pmn . ')' : '' ?>" title="<?= e(t('Messages')) ?>">✉<?php if ($pmn): ?><b class="pm-dot"><?= $pmn > 9 ? '9+' : $pmn ?></b><?php endif ?></a><?php endif ?>
      <?php if (setting('appearance.allow_user_mode', true) && MagicCMS\Core\Appearance::lockedMode() === null): ?><button class="icon-btn mode-toggle" aria-label="<?= e(t('Switch light/dark mode')) ?>" title="<?= e(t('Switch light/dark mode')) ?>"><span class="mode-icon" aria-hidden="true"></span></button><?php endif ?>
      <?php if ($u): ?>
        <a class="header-user" href="<?= e(url('profile')) ?>"><img class="avatar avatar-md" src="<?= e(avatar_url($u, 64)) ?>" alt="" width="44" height="44"><span class="hu-text"><b><?= e($u['username']) ?></b><small><?= e($u['role_name']) ?></small></span></a>
        <?php if (can('admin.access')): ?><a class="btn btn-sm btn-ghost hide-mobile" href="<?= e(url('admin')) ?>"><?= e(t('Admin')) ?></a><?php endif ?>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= e(url('login')) ?>"><?= e(t('Login')) ?></a>
        <?php if (setting('auth.registration', 'open') !== 'closed'): ?><a class="btn btn-primary hide-mobile" href="<?= e(url('register')) ?>"><?= e(t('Register')) ?></a><?php endif ?>
      <?php endif ?>
      <button class="btn btn-ghost hdr-menu" type="button" aria-expanded="false" aria-label="<?= e(t('Menu')) ?>"><span class="hdr-burger" aria-hidden="true"></span><span class="hdr-menu-t"><?= e(t('Menu')) ?></span></button>
    </div>
<?php return (string) ob_get_clean(); };
$classic = static function (string $wrap = '') use ($brandHtml, $actionsHtml, $navItems): string { ob_start(); ?>
<?= $wrap ? '<div class="' . e($wrap) . '">' : '' ?>
<header class="site-header" id="top">
  <div class="container header-top">
    <?= $brandHtml() ?>
    <?= $actionsHtml() ?>
  </div>
</header>
<div class="nav-sentinel" aria-hidden="true"></div>
<div class="site-navbar">
  <div class="container navbar-inner">
    <a class="nav-mini-brand" href="<?= e(url('')) ?>" aria-label="<?= e(setting('site.name', 'MagicCMS')) ?>"><img src="<?= e(site_logo()) ?>" alt="" height="30"></a>
    <?= render('nav', ['items' => $navItems, 'class' => 'site-nav']) ?>
  </div>
</div>
<?= $wrap ? '</div>' : '' ?>
<?php return (string) ob_get_clean(); };

switch ($style):
  case 'inline': ?>
<header class="site-header hd-inline" id="top">
  <div class="container hd-row">
    <?= $brandHtml() ?>
    <?= render('nav', ['items' => $navItems, 'class' => 'site-nav']) ?>
    <?= $actionsHtml() ?>
  </div>
</header>
<?php break;
  case 'topbar':
    $tbItems = $navItems;
    if (count($tbItems) > 6) {   // the top bar shows at most 6 buttons; the rest move into a "More" dropdown
        $keep = array_slice($tbItems, 0, 5);
        $more = [];
        foreach (array_slice($tbItems, 5) as $it) {
            $kids = $it['children'];
            $it['children'] = [];
            $more[] = $it;
            foreach ($kids as $k) { $more[] = $k; }
        }
        $keep[] = ['label' => t('More'), 'url' => '#', 'icon' => '⋯', 'badge' => '', 'color' => '', 'new_tab' => false, 'hideMobile' => false,
                   'active' => (bool) array_filter($more, static fn($m) => !empty($m['active'])), 'children' => $more];
        $tbItems = $keep;
    } ?>
<div class="hd-topbar<?= setting('layout.topbar_sticky', true) ? ' is-sticky' : '' ?>" id="top">
  <div class="container hd-tb-row">
    <?= render('nav', ['items' => $tbItems, 'class' => 'site-nav']) ?>
    <?= $actionsHtml() ?>
  </div>
</div>
<header class="site-header hd-tb-brand align-<?= e(MagicCMS\Core\Layout::brandAlign()) ?>">
  <div class="container"><?= $brandHtml() ?></div>
</header>
<?php break;
  case 'hanging': ?>
<header class="site-header hd-hanging" id="top">
  <div class="container hd-stage">
    <div class="hd-badge"><?= $brandHtml('hd-brand') ?></div>
    <div class="hd-top"><?= $actionsHtml() ?></div>
    <div class="hd-bar"><?= render('nav', ['items' => $navItems, 'class' => 'site-nav']) ?></div>
  </div>
</header>
<?php break;
  case 'float': ?>
<header class="site-header hd-float" id="top">
  <div class="container hd-islands">
    <div class="hd-island hd-i-brand"><?= $brandHtml() ?></div>
    <div class="hd-island hd-i-nav"><?= render('nav', ['items' => $navItems, 'class' => 'site-nav']) ?></div>
    <div class="hd-island hd-i-act"><?= $actionsHtml() ?></div>
  </div>
</header>
<?php break;
  case 'rail': ?>
<aside class="site-rail" aria-label="<?= e(t('Site')) ?>">
  <div class="rail-brand"><?= $brandHtml() ?></div>
  <div class="rail-nav"><?= render('nav', ['items' => $navItems, 'class' => 'site-nav nav-vertical']) ?></div>
  <div class="rail-actions"><?= $actionsHtml() ?></div>
</aside>
<?= $classic('hd-rail-mobile') ?>
<?php break;
  default: ?>
<?= $classic() ?>
<?php endswitch ?>
