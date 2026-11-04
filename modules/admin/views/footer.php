<?php /** @var array $f @var string $copy */ use MagicCMS\Core\Footer;
$chk = static fn(string $k, string $label) => '<label class="checkbox"><input type="checkbox" name="' . $k . '" value="1" ' . (!empty($f[$k]) ? 'checked' : '') . '> ' . e(t($label)) . '</label>';
$groups = array_pad($f['groups'], Footer::MAX_GROUPS, ['title' => '', 'links' => []]); ?>
<form method="post" action="<?= e(url('admin/footer')) ?>"><?= csrf_field() ?>
<p class="muted"><?= e(t('The footer works with every theme – the theme only decides colours and shapes. Extra content blocks: Admin → Panels → “Footer columns” (custom HTML, navigation, latest news, online users …).')) ?></p>

<div class="card"><h2 class="card-title"><?= e(t('Layout')) ?></h2><div class="card-body">
  <div class="hs-picker" role="radiogroup" aria-label="<?= e(t('Footer style')) ?>">
  <?php foreach (Footer::STYLES as $k => $label): [$name, $desc] = array_pad(explode(' – ', $label, 2), 2, ''); ?>
    <label class="hs-opt"><input type="radio" name="style" value="<?= e($k) ?>" <?= $f['style'] === $k ? 'checked' : '' ?>><span class="hs-mini fs-<?= e($k) ?>" aria-hidden="true"><i class="a"></i><i class="b"></i><i class="c"></i><i class="d"></i></span><b><?= e(t($name)) ?></b><small><?= e(t($desc)) ?></small></label>
  <?php endforeach ?></div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Top decoration')) ?></label><select class="select" name="deco"><?php foreach (Footer::DECOS as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['deco'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Copyright line ({year} = current year)')) ?></label><input class="input" name="copyright" maxlength="200" value="<?= e($copy) ?>" placeholder="© {year} <?= e(setting('site.name')) ?>"></div>
  </div>
  <h3 class="section-title"><?= e(t('Footer logo and name')) ?></h3>
  <p class="muted small"><?= e(t('These sizes apply only to the footer. The header has its own settings in Admin → Settings.')) ?></p>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Footer logo height')) ?> (<output id="o-flogo"><?= (int) $f['logo_size'] ?: t('auto') ?></output>)</label><input type="range" name="logo_size" min="0" max="160" step="2" value="<?= (int) $f['logo_size'] ?>" data-out="o-flogo"><small class="help"><?= e(t('0 = automatic (the theme decides).')) ?></small></div>
    <div class="form-row"><label class="label"><?= e(t('Footer site name size')) ?> (<output id="o-fname"><?= (int) $f['name_size'] ?: t('auto') ?></output>)</label><input type="range" name="name_size" min="0" max="60" step="1" value="<?= (int) $f['name_size'] ?>" data-out="o-fname"></div>
  </div>
  <div class="check-grid"><?= $chk('name_show', 'Show the site name next to the footer logo') ?></div>
  <div class="check-grid"><?= $chk('wordmark', 'Giant outlined site name behind the footer') ?><?= $chk('stats', 'Live strip: members online, member count, next match') ?><?= $chk('totop', 'Back-to-top button') ?><?= $chk('powered', '“Powered by MagicCMS” credit') ?></div>
</div></div>

<div class="card"><h2 class="card-title"><?= e(t('Content')) ?></h2><div class="card-body">
  <div class="check-grid"><?= $chk('brand', 'Logo, name and short text') ?><?= $chk('explore', '“Explore” column (main menu)') ?><?= $chk('community', '“Community” column (footer menu)') ?><?= $chk('news', '“Latest news” column') ?></div>
  <div class="form-row"><label class="label"><?= e(t('Short text next to the logo (empty = site description)')) ?></label><textarea class="textarea" name="about" rows="2" maxlength="400"><?= e($f['about']) ?></textarea></div>
  <h3 class="section-title"><?= e(t('Own link columns (up to 3)')) ?></h3>
  <p class="muted small"><?= e(t('One link per line: Label | https://… (or /page, #anchor). Up to {n} links per column.', ['n' => Footer::MAX_LINKS])) ?></p>
  <div class="form-grid">
  <?php foreach ($groups as $i => $g): ?><div class="form-row"><input class="input" name="g_title[<?= $i ?>]" maxlength="40" placeholder="<?= e(t('Column title')) ?>" value="<?= e($g['title']) ?>"><textarea class="textarea mt" name="g_links[<?= $i ?>]" rows="5" placeholder="Rules | /p/rules&#10;Discord | https://discord.gg/…"><?= e(implode("\n", array_map(static fn($l) => $l[0] . ' | ' . $l[1], $g['links']))) ?></textarea></div><?php endforeach ?>
  </div>
</div></div>

<div class="card"><h2 class="card-title"><?= e(t('Social buttons')) ?></h2><div class="card-body">
  <p class="muted small"><?= e(t('Round buttons that light up in the network colour on hover. Leave empty to hide.')) ?></p>
  <div class="form-grid">
  <?php foreach (Footer::SOCIALS as $k => [$label, $badge, $col]): $v = $f['socials'][$k] ?? ''; ?><div class="form-row"><label class="label"><span class="f-social f-social-sm" style="--sc:<?= e($col) ?>"><span><?= e($badge) ?></span></span> <?= e($label) ?></label><input class="input" name="social[<?= e($k) ?>]" maxlength="300" value="<?= e($k === 'email' ? preg_replace('/^mailto:/', '', $v) : $v) ?>" placeholder="<?= $k === 'email' ? 'club@example.com' : 'https://…' ?>"></div><?php endforeach ?>
  </div>
</div></div>

<div class="card"><h2 class="card-title"><?= e(t('Scrolling ticker')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Show')) ?></label><select class="select" name="ticker"><?php foreach (Footer::TICKERS as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['ticker'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Direction')) ?></label><select class="select" name="ticker_dir"><option value="right" <?= $f['ticker_dir'] === 'right' ? 'selected' : '' ?>><?= e(t('Left → right (opposite to the partners strip)')) ?></option><option value="left" <?= $f['ticker_dir'] === 'left' ? 'selected' : '' ?>><?= e(t('Right → left')) ?></option></select></div>
    <div class="form-row"><label class="label"><?= e(t('Speed (1 slow … 10 fast)')) ?></label><input class="input" type="number" min="1" max="10" name="ticker_speed" value="<?= (int) $f['ticker_speed'] ?>"></div>
  </div>
  <p class="muted small"><?= e(t('A live line of headlines, results and upcoming events along the top of the footer. Pauses on hover.')) ?></p>
</div></div>
<p><button class="btn btn-primary"><?= e(t('Save footer')) ?></button> <a class="btn btn-ghost" href="<?= e(url('')) ?>#footer"><?= e(t('View site')) ?></a></p>
</form>
