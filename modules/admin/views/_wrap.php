<?php /** @var string $tab @var string $title @var string $inner */
$tabs = [
  'dashboard' => ['/admin', 'Dashboard', 'admin.access'], 'settings' => ['/admin/settings', 'Settings', 'admin.settings'],
  'appearance' => ['/admin/appearance', 'Appearance', 'admin.appearance'], 'navigation' => ['/admin/navigation', 'Navigation', 'admin.navigation'], 'footer' => ['/admin/footer', 'Footer', 'admin.appearance'],
  'panels' => ['/admin/panels', 'Panels', 'admin.panels'], 'forum' => ['/admin/forum', 'Forum', 'admin.forum'], 'news' => ['/admin/news', 'News', 'admin.news'], 'hero' => ['/admin/hero', 'Hero', 'admin.hero'], 'pages' => ['/admin/pages', 'Pages', 'admin.pages'], 'users' => ['/admin/users', 'Users', 'admin.users'], 'modules' => ['/admin/modules', 'Modules', 'admin.modules'],
]; $tabs = MagicCMS\Core\Hooks::filter('admin.tabs', $tabs);

/* Sidebar grouping + icons (24×24 stroke paths). Unknown tabs added by plugins land in "Extensions". */
$groups = [
  'Overview'  => ['dashboard'],
  'Content'   => ['news', 'pages', 'hero', 'forum', 'partners'],
  'Site'      => ['settings', 'appearance', 'navigation', 'footer', 'panels'],
  'Community' => ['users', 'recruit'],
  'Esports'   => ['matches', 'roster', 'events'],
  'System'    => ['modules', 'maintenance'],
];
$icons = [
  'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.6"/><rect x="14" y="3" width="7" height="5" rx="1.6"/><rect x="14" y="12" width="7" height="9" rx="1.6"/><rect x="3" y="16" width="7" height="5" rx="1.6"/>',
  'settings' => '<path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2.2"/><circle cx="9" cy="17" r="2.2"/>',
  'appearance' => '<path d="M12 3a9 9 0 100 18c1.6 0 2-1 1.4-2-.7-1.2 0-2.5 1.4-2.5H17a4 4 0 004-4c0-5-4-9.5-9-9.5z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7" r="1"/><circle cx="15" cy="7.5" r="1"/>',
  'navigation' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
  'footer' => '<rect x="3" y="4" width="18" height="16" rx="2.4"/><path d="M3 15h18"/>',
  'panels' => '<rect x="3" y="4" width="8" height="16" rx="2.2"/><rect x="13" y="4" width="8" height="7" rx="2.2"/><rect x="13" y="14" width="8" height="6" rx="2.2"/>',
  'forum' => '<path d="M4 5.5A1.5 1.5 0 015.5 4h13A1.5 1.5 0 0120 5.5v9a1.5 1.5 0 01-1.5 1.5H10l-4.5 4v-4h0A1.5 1.5 0 014 14.5z"/>',
  'news' => '<path d="M5 4h11l3 3v13H5z"/><path d="M8 10h8M8 14h8M8 17h5"/>',
  'hero' => '<rect x="3" y="5" width="18" height="14" rx="2.4"/><circle cx="9" cy="10" r="1.6"/><path d="M3.5 17l5-4 4 3 3-2.5 5 3.5"/>',
  'pages' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
  'users' => '<circle cx="9" cy="8" r="3.4"/><path d="M2.8 20c0-3.5 2.8-5.8 6.2-5.8s6.2 2.3 6.2 5.8"/><path d="M16 5.2a3.4 3.4 0 010 6.4M18.2 14.6c2 .7 3.2 2.6 3.2 5.4"/>',
  'modules' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>',
  'matches' => '<path d="M5 5l14 14M19 5L5 19"/><circle cx="12" cy="12" r="2"/>',
  'roster' => '<path d="M12 3l7.5 3v5.3c0 4.7-3.1 8-7.5 9.7-4.4-1.7-7.5-5-7.5-9.7V6z"/>',
  'events' => '<rect x="3" y="5" width="18" height="16" rx="2.4"/><path d="M3 10h18M8 3v4M16 3v4"/>',
  'recruit' => '<circle cx="9" cy="8" r="3.4"/><path d="M2.8 20c0-3.5 2.8-5.8 6.2-5.8 1.4 0 2.6.4 3.6 1M18 14v6M15 17h6"/>',
  'partners' => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
  'maintenance' => '<path d="M12 4v11M7 10.5l5 5 5-5M4 20h16"/>',
  '_' => '<circle cx="12" cy="12" r="3"/>',
];
$svg = static fn(string $k): string => '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$k] ?? $icons['_']) . '</svg>';
$placed = [];
$nav = [];
foreach ($groups as $g => $keys) { foreach ($keys as $k) { if (isset($tabs[$k]) && can($tabs[$k][2])) { $nav[$g][] = $k; $placed[$k] = 1; } } }
foreach ($tabs as $k => $tdef) { if (!isset($placed[$k]) && can($tdef[2])) { $nav['Extensions'][] = $k; } }
$u = user(); $site = setting('site.name', 'MagicCMS'); ?>
<aside class="adm-side" id="adm-side" aria-label="<?= e(t('Admin')) ?>">
  <a class="adm-brand" href="<?= e(url('admin')) ?>"><img src="<?= e(site_logo()) ?>" alt="" height="38"><span><b><?= e($site) ?></b><small><?= e(t('Control panel')) ?></small></span></a>
  <nav class="adm-nav">
  <?php foreach ($nav as $g => $keys): ?>
    <div class="adm-group"><span class="adm-group-t"><?= e(t($g)) ?></span>
    <?php foreach ($keys as $k): [$href, $label] = $tabs[$k]; ?>
      <a class="adm-link<?= $k === $tab ? ' is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $k === $tab ? ' aria-current="page"' : '' ?>><?= $svg($k) ?><span><?= e(t($label)) ?></span></a>
    <?php endforeach ?></div>
  <?php endforeach ?>
  </nav>
  <div class="adm-side-foot"><a class="adm-link" href="<?= e(url('')) ?>"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg><span><?= e(t('View site')) ?></span></a><small class="adm-ver">MagicCMS <?= e(MCMS_VERSION) ?></small></div>
</aside>
<div class="adm-scrim" data-adm-close></div>
<div class="adm-main">
  <header class="adm-top">
    <button class="adm-burger" type="button" aria-label="<?= e(t('Menu')) ?>" aria-controls="adm-side" data-adm-open><span></span><span></span><span></span></button>
    <div class="adm-crumbs"><span><?= e(t('Admin')) ?></span><i aria-hidden="true">/</i><b><?= e($title) ?></b></div>
    <div class="adm-find" data-adm-find>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/></svg>
      <input type="search" placeholder="<?= e(t('Jump to…')) ?>" aria-label="<?= e(t('Jump to…')) ?>" autocomplete="off"><kbd>Ctrl K</kbd>
      <ul class="adm-find-list" hidden></ul>
    </div>
    <div class="adm-top-r">
      <?php if (setting('appearance.allow_user_mode', true)): ?><button class="icon-btn mode-toggle" type="button" aria-label="<?= e(t('Switch light/dark mode')) ?>" title="<?= e(t('Switch light/dark mode')) ?>"><span class="mode-icon" aria-hidden="true"></span></button><?php endif ?>
      <?php if ($u): ?>
      <div class="adm-user"><img class="avatar" src="<?= e(avatar_url($u, 64)) ?>" alt="" width="34" height="34"><span><b><?= e($u['username']) ?></b><small><?= e($u['role_name']) ?></small></span></div>
      <form method="post" action="<?= e(url('logout')) ?>" class="adm-logout"><?= csrf_field() ?><button class="icon-btn" type="submit" title="<?= e(t('Log out')) ?>" aria-label="<?= e(t('Log out')) ?>"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4M16 8l4 4-4 4M20 12H9"/></svg></button></form>
      <?php endif ?>
    </div>
  </header>
  <main class="adm-content" id="main">
    <?php foreach (flashes() as [$type, $msg]): ?><div class="alert alert-<?= e($type) ?>" role="status"><?= e($msg) ?></div><?php endforeach ?>
    <h1 class="adm-title"><?= e($title) ?></h1>
    <?= $inner ?>
  </main>
</div>
<script src="<?= e(asset('admin.js')) ?>" defer></script>
<script src="<?= e(asset('editor.js')) ?>" defer></script>
