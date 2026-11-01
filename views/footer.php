<?php
use MagicCMS\Core\{Footer, Nav, Panels};
$f = Footer::config();
$items = Nav::tree('main');
$community = Nav::tree('footer');
if (!$community) { // sensible defaults until the admin fills the footer menu
    $community = [];
    if (!user()) {
        $community[] = ['label' => t('Login'), 'url' => url('login')];
        if (setting('auth.registration', 'open') !== 'closed') { $community[] = ['label' => t('Register'), 'url' => url('register')]; }
    }
    $community[] = ['label' => t('Members'), 'url' => url('members')];
    if (Footer::moduleOn('news')) { $community[] = ['label' => 'RSS', 'url' => url('news/feed.xml')]; }
}
$latest = [];
if ($f['news'] && Footer::moduleOn('news')) {
    foreach (MagicCMS\Modules\News\News::latest(3) as $p) { $latest[] = ['label' => $p['title'], 'url' => url('news/' . $p['slug']), 'sub' => time_ago($p['publish_at'])]; }
}
$custom = has_custom_logo(); $showName = show_site_name();
$about = $f['about'] !== '' ? $f['about'] : (string) setting('site.description', '');
$panels = Panels::render('footer');
$ticker = $f['ticker'] !== 'none' ? Footer::tickerItems($f['ticker']) : [];
$socials = array_intersect_key($f['socials'], Footer::SOCIALS);
$style = $f['style'];
$word = $f['wordmark'] || $style === 'poster';
$socialHtml = static function () use ($socials): void {
    if (!$socials) { return; }
    echo '<ul class="f-socials" aria-label="' . e(t('Social links')) . '">';
    foreach ($socials as $k => $u) {
        [$label, $badge, $col] = Footer::SOCIALS[$k];
        echo '<li><a class="f-social" href="' . e($u) . '" target="_blank" rel="noopener noreferrer me" style="--sc:' . e($col) . '" title="' . e($label) . '" aria-label="' . e($label) . '"><span aria-hidden="true">' . e($badge) . '</span></a></li>';
    }
    echo '</ul>';
};
$ticks = '';
foreach ($ticker as [$glyph, $text, $href]) {
    $ticks .= '<li><a href="' . e($href) . '"><i aria-hidden="true">' . e($glyph) . '</i><span>' . e($text) . '</span></a></li>';
}
$reps = $ticker ? max(1, (int) ceil(10 / count($ticker))) : 1;
?>
<footer class="site-footer" id="footer" data-fstyle="<?= e($style) ?>" data-deco="<?= e($f['deco']) ?>">
  <div class="footer-glow" aria-hidden="true"></div>
  <?php if (in_array($f['deco'], ['aurora', 'tape'], true)): ?><div class="footer-deco deco-<?= e($f['deco']) ?>" aria-hidden="true"></div><?php endif ?>
  <?php if ($ticker): ?>
  <div class="footer-ticker dir-<?= e($f['ticker_dir']) ?>" style="--ft-dur:<?= round(count($ticker) * $reps * (7.4 - $f['ticker_speed'] * 0.62), 1) ?>s" aria-label="<?= e(t('Latest')) ?>">
    <ul class="ft-track"><?= str_repeat($ticks, $reps) ?><?= str_replace('<a ', '<a tabindex="-1" aria-hidden="true" ', str_repeat($ticks, $reps)) ?></ul>
  </div>
  <?php endif ?>
  <?php if ($f['stats']): ?>
  <div class="container footer-stats" role="status"><?php foreach (Footer::stats() as $i => [$k, $v, $l]): ?><span class="fs-<?= e($k) ?>"><?= $k === 'online' ? '<i class="fs-dot" aria-hidden="true"></i>' : '' ?><b><?= e($v) ?></b> <?= e($l) ?></span><?php endforeach ?></div>
  <?php endif ?>
  <div class="container footer-grid">
    <?php if ($f['brand']): ?>
    <div class="fcol fbrand">
      <a class="brand brand-sm<?= $showName ? '' : ' brand-only' ?>" href="<?= e(url('')) ?>"><img class="<?= $custom ? 'brand-logo' : 'brand-mark' ?>" src="<?= e(site_logo()) ?>" alt="<?= $showName ? '' : e(setting('site.name', 'MagicCMS')) ?>" height="40"<?= $custom ? '' : ' width="40"' ?>><?php if ($showName): ?><span class="brand-text"><b class="brand-name"><?= e(setting('site.name', 'MagicCMS')) ?></b><?php if ((string) setting('site.tagline', '') !== ''): ?><small class="brand-sub"><?= e(setting('site.tagline')) ?></small><?php endif ?></span><?php endif ?></a>
      <?php if ($about !== ''): ?><p class="muted f-about"><?= e($about) ?></p><?php endif ?>
      <?php $socialHtml() ?>
    </div>
    <?php elseif ($socials): ?><div class="fcol"><?php $socialHtml() ?></div><?php endif ?>
    <?php if ($f['explore']): ?><nav class="fcol" aria-label="<?= e(t('Explore')) ?>"><h4><?= e(t('Explore')) ?></h4><ul><?php foreach (array_slice($items, 0, 7) as $i): ?><li><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a></li><?php endforeach ?></ul></nav><?php endif ?>
    <?php if ($f['community']): ?><nav class="fcol" aria-label="<?= e(t('Community')) ?>"><h4><?= e(t('Community')) ?></h4><ul><?php foreach ($community as $i): ?><li><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a></li><?php endforeach ?></ul></nav><?php endif ?>
    <?php foreach ($f['groups'] as $g): ?><nav class="fcol" aria-label="<?= e($g['title']) ?>"><h4><?= e($g['title']) ?></h4><ul><?php foreach ($g['links'] as [$label, $href]): $ext = preg_match('#^https?://#i', $href); ?><li><a href="<?= e($href) ?>"<?= $ext ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($label) ?></a></li><?php endforeach ?></ul></nav><?php endforeach ?>
    <?php if ($latest): ?><div class="fcol"><h4><?= e(t('Latest news')) ?></h4><ul class="flatest"><?php foreach ($latest as $i): ?><li><a href="<?= e($i['url']) ?>"><?= e($i['label']) ?></a><small class="muted"><?= e($i['sub']) ?></small></li><?php endforeach ?></ul></div><?php endif ?>
    <?= implode("\n", $panels) ?>
  </div>
  <?php if ($word): ?><div class="footer-wordmark" aria-hidden="true"><span><?= e(setting('site.name', 'MagicCMS')) ?></span></div><?php endif ?>
  <div class="container footer-bottom">
    <span class="f-copy"><?= e(str_replace('{year}', gmdate('Y'), trim((string) setting('site.footer', '')) !== '' ? (string) setting('site.footer') : '© {year} ' . setting('site.name'))) ?></span>
    <?php if ($f['powered']): ?><span class="powered"><img src="<?= e(asset('logo-mark.svg')) ?>" alt="" width="18" height="18"> <?= e(t('Powered by')) ?> <a href="https://github.com/magicbf/MagicCMS" rel="noopener">MagicCMS</a> <?= e(MCMS_VERSION) ?></span><?php endif ?>
    <?php if ($f['totop']): ?><a class="to-top" href="#top" aria-label="<?= e(t('Back to top')) ?>">↑</a><?php endif ?>
  </div>
</footer>
