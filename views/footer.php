<?php
$items = MagicCMS\Core\Nav::tree('main');
$community = MagicCMS\Core\Nav::tree('footer');
if (!$community) { // sensible defaults until the admin fills the footer menu
    $community = [];
    if (!user()) {
        $community[] = ['label' => t('Login'), 'url' => url('login')];
        if (setting('auth.registration', 'open') !== 'closed') { $community[] = ['label' => t('Register'), 'url' => url('register')]; }
    }
    $community[] = ['label' => t('Members'), 'url' => url('members')];
    if (db()->val("SELECT 1 FROM {modules} WHERE module_key = 'news' AND enabled = 1")) { $community[] = ['label' => 'RSS', 'url' => url('news/feed.xml')]; }
}
$latest = [];
if (db()->val("SELECT 1 FROM {modules} WHERE module_key = 'news' AND enabled = 1")) {
    foreach (MagicCMS\Modules\News\News::latest(3) as $p) { $latest[] = ['label' => $p['title'], 'url' => url('news/' . $p['slug']), 'sub' => time_ago($p['publish_at'])]; }
}
$custom = has_custom_logo(); ?>
<footer class="site-footer">
  <div class="footer-glow" aria-hidden="true"></div>
  <div class="container footer-grid">
    <div class="fcol fbrand">
      <a class="brand brand-sm" href="<?= e(url('')) ?>"><img class="<?= $custom ? 'brand-logo' : 'brand-mark' ?>" src="<?= e(site_logo()) ?>" alt="" height="40"<?= $custom ? '' : ' width="40"' ?>><span class="brand-text"><b class="brand-name"><?= e(setting('site.name', 'MagicCMS')) ?></b><?php if ((string) setting('site.tagline', '') !== ''): ?><small class="brand-sub"><?= e(setting('site.tagline')) ?></small><?php endif ?></span></a>
      <?php if ((string) setting('site.description', '') !== ''): ?><p class="muted"><?= e(setting('site.description')) ?></p><?php endif ?>
    </div>
    <nav class="fcol" aria-label="<?= e(t('Explore')) ?>"><h4><?= e(t('Explore')) ?></h4><ul><?php foreach (array_slice($items, 0, 7) as $i): ?><li><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a></li><?php endforeach ?></ul></nav>
    <nav class="fcol" aria-label="<?= e(t('Community')) ?>"><h4><?= e(t('Community')) ?></h4><ul><?php foreach ($community as $i): ?><li><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a></li><?php endforeach ?></ul></nav>
    <?php if ($latest): ?><div class="fcol"><h4><?= e(t('Latest news')) ?></h4><ul class="flatest"><?php foreach ($latest as $i): ?><li><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><small class="muted"><?= e($i['sub']) ?></small></li><?php endforeach ?></ul></div><?php endif ?>
  </div>
  <div class="container footer-bottom">
    <span><?= e(str_replace('{year}', gmdate('Y'), trim((string) setting('site.footer', '')) !== '' ? (string) setting('site.footer') : '© {year} ' . setting('site.name'))) ?></span>
    <span class="powered"><img src="<?= e(asset('logo-mark.svg')) ?>" alt="" width="18" height="18"> <?= e(t('Powered by')) ?> <a href="https://github.com/magicbf/MagicCMS" rel="noopener">MagicCMS</a> <?= e(MCMS_VERSION) ?></span>
    <a class="to-top" href="#top" aria-label="<?= e(t('Back to top')) ?>">↑</a>
  </div>
</footer>
