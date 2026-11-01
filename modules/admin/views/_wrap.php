<?php /** @var string $tab @var string $title @var string $inner */
$tabs = [
  'dashboard' => ['/admin', 'Dashboard', 'admin.access'], 'settings' => ['/admin/settings', 'Settings', 'admin.settings'],
  'appearance' => ['/admin/appearance', 'Appearance', 'admin.appearance'], 'navigation' => ['/admin/navigation', 'Navigation', 'admin.navigation'],
  'panels' => ['/admin/panels', 'Panels', 'admin.panels'], 'forum' => ['/admin/forum', 'Forum', 'admin.forum'], 'news' => ['/admin/news', 'News', 'admin.news'], 'hero' => ['/admin/hero', 'Hero', 'admin.hero'], 'pages' => ['/admin/pages', 'Pages', 'admin.pages'], 'users' => ['/admin/users', 'Users', 'admin.users'],
]; ?>
<div class="admin-head"><h1 class="admin-title"><?= e($title) ?></h1><a class="btn btn-ghost btn-sm" href="<?= e(url('')) ?>">← <?= e(t('Back to site')) ?></a></div>
<nav class="tabs" aria-label="Admin"><?php foreach ($tabs as $k => [$href, $label, $perm]): if (!can($perm)) continue; ?>
  <a class="tab<?= $k === $tab ? ' is-active' : '' ?>" href="<?= e(url($href)) ?>"><?= e(t($label)) ?></a><?php endforeach ?></nav>
<?= $inner ?>
<script src="<?= e(asset('admin.js')) ?>" defer></script>
