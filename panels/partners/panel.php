<?php
/** Partners strip: logos scroll right → left in an endless loop. Rendered full width above the footer (bare panel). @var array $settings @var array $panel */
$rows = db()->all('SELECT name, url, logo FROM {partners} WHERE enabled = 1 ORDER BY sort_order, id');
$items = [];
foreach ($rows as $r) {
    if ($src = upload_url('partners', $r['logo'])) {
        $items[] = ['name' => $r['name'], 'url' => $r['url'], 'src' => $src];
    }
}
if (!$items) {
    return;
}
$h = max(24, min(120, (int) ($settings['height'] ?? 56)));
$speed = max(1, min(10, (int) ($settings['speed'] ?? 5)));
$reps = max(1, (int) ceil(14 / count($items)));            // enough logos per half to always overfill the screen
$half = array_merge(...array_fill(0, $reps, $items));
$dur = round(count($half) * (7.2 - $speed * 0.62), 1);       // seconds for one full loop (slow=…fast)
$cls = 'partners-strip' . (!empty($settings['grayscale']) ? ' is-muted' : '') . (!empty($settings['pause']) ? ' is-pausable' : '');
$render = static function (array $set, bool $dup): void {
    foreach ($set as $it) {
        $img = '<img src="' . e($it['src']) . '" alt="' . ($dup ? '' : e($it['name'])) . '" loading="lazy" decoding="async">';
        echo '<li>' . ($it['url'] !== '' ? '<a href="' . e($it['url']) . '" target="_blank" rel="noopener sponsored" title="' . e($it['name']) . '"' . ($dup ? ' tabindex="-1" aria-hidden="true"' : '') . '>' . $img . '</a>' : '<span title="' . e($it['name']) . '">' . $img . '</span>') . '</li>';
    }
};
?>
<section class="<?= e($cls) ?>" data-panel="partners" aria-label="<?= e($panel['title']) ?>">
  <div class="container">
    <?php if (trim((string) ($settings['heading'] ?? '')) !== ''): ?><h2 class="partners-title"><span><?= e($settings['heading']) ?></span></h2><?php endif ?>
    <div class="partners-marquee" style="--pn-h:<?= $h ?>px;--pn-dur:<?= $dur ?>s">
      <ul class="partners-track"><?php $render($half, false); $render($half, true); ?></ul>
    </div>
  </div>
</section>
