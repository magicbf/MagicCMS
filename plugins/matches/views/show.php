<?php /** @var array $m */ use MagicCMS\Plugins\Matches\Matches;
$res = Matches::result($m);
$us = (int) $m['score_us']; $them = (int) $m['score_them'];
$word = ['win' => t('Victory'), 'loss' => t('Defeat'), 'draw' => t('Draw')][$res ?? ''] ?? t('Upcoming');
$sec = strtotime($m['played_at'] . ' UTC') - time();
$when = '';
if (!$res && $sec > 0) {
    $when = $sec < 3600 ? t('starting soon') : ($sec < 86400 ? t('in {n} h', ['n' => (int) round($sec / 3600)]) : t('in {n} d', ['n' => (int) floor($sec / 86400)]));
}
$crest = static fn(string $tag, string $name): string => mb_strtoupper(mb_substr(trim($tag) !== '' ? $tag : preg_replace('/[^\p{L}\p{N}]+/u', '', $name), 0, 3));
$h2h = db()->all("SELECT score_us, score_them, status FROM {matches} WHERE LOWER(opponent) = LOWER(?) AND id <> ? AND status = 'finished'", [$m['opponent'], (int) $m['id']]);
$rec = ['win' => 0, 'loss' => 0, 'draw' => 0];
foreach ($h2h as $h) { if ($r = Matches::result($h)) { $rec[$r]++; } }
$prev = db()->one("SELECT id, opponent FROM {matches} WHERE (played_at < ? OR (played_at = ? AND id < ?)) ORDER BY played_at DESC, id DESC LIMIT 1", [$m['played_at'], $m['played_at'], (int) $m['id']]);
$nextM = db()->one("SELECT id, opponent FROM {matches} WHERE (played_at > ? OR (played_at = ? AND id > ?)) ORDER BY played_at ASC, id ASC LIMIT 1", [$m['played_at'], $m['played_at'], (int) $m['id']]);
$tag = (string) $m['opponent_tag'];
?>
<nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('matches')) ?>"><?= e(t('Matches')) ?></a> <span class="sep">›</span> <span>vs <?= e($m['opponent']) ?></span></nav>
<article class="card match-detail match-<?= e($res ?? 'upcoming') ?>">
  <h1 class="card-title md-title"><?= e($m['competition'] ?: t('Match')) ?></h1>
  <div class="card-body">
    <div class="md-versus">
      <div class="md-team md-us<?= $res === 'win' ? ' is-winner' : ($res === 'loss' ? ' is-loser' : '') ?>"><span class="md-logo"><?php if (has_custom_logo()): ?><?= logo_img('class="{cls}" alt=""') ?><?php else: ?><?= e(mb_strtoupper(mb_substr((string) setting('site.name', 'US'), 0, 3))) ?><?php endif ?></span><strong><?= e(setting('site.name')) ?></strong></div>
      <div class="md-score" role="img" aria-label="<?= $res ? e($us . ' : ' . $them . ', ' . $word) : e(t('Upcoming')) ?>">
        <?php if ($res): ?><div class="md-nums"><b class="<?= $res === 'win' ? 'hi' : ($res === 'loss' ? 'lo' : '') ?>"><?= $us ?></b><i>:</i><b class="<?= $res === 'loss' ? 'hi' : ($res === 'win' ? 'lo' : '') ?>"><?= $them ?></b></div>
        <?php else: ?><div class="md-nums md-vs"><b>VS</b></div><?php endif ?>
        <span class="md-result"><?= e($when !== '' ? $word . ' · ' . $when : $word) ?></span>
        <?php if ($res && $us !== $them): ?><small class="md-margin"><?= e(t('+{n} margin', ['n' => abs($us - $them)])) ?></small><?php endif ?>
      </div>
      <div class="md-team md-them<?= $res === 'loss' ? ' is-winner' : ($res === 'win' ? ' is-loser' : '') ?>"><span class="md-logo alt"><?= e($crest($tag, (string) $m['opponent'])) ?></span><strong><?= e($m['opponent']) ?></strong></div>
    </div>
    <dl class="md-facts"><div><dt>📅 <?= e(t('Date')) ?></dt><dd><?= e(fmt_date($m['played_at'], 'Y-m-d H:i')) ?></dd></div>
      <?php if ($m['game'] !== ''): ?><div><dt>🎮 <?= e(t('Game')) ?></dt><dd><?= e($m['game']) ?></dd></div><?php endif ?>
      <?php if ($m['map'] !== ''): ?><div><dt>🗺️ <?= e(t('Map')) ?></dt><dd><?= e($m['map']) ?></dd></div><?php endif ?>
      <?php if (array_sum($rec) > 0): ?><div><dt>⚔️ <?= e(t('Head to head')) ?></dt><dd class="md-h2h"><span class="w"><?= $rec['win'] ?>W</span> <span class="d"><?= $rec['draw'] ?>D</span> <span class="l"><?= $rec['loss'] ?>L</span> <small><?= e(t('before this match')) ?></small></dd></div><?php endif ?></dl>
    <?php if ($m['vod_url'] !== ''): ?><p><a class="btn btn-primary btn-sm" href="<?= e($m['vod_url']) ?>" target="_blank" rel="noopener noreferrer">▶ <?= e(t('Watch the VOD')) ?></a></p><?php endif ?>
    <?php if (!empty($m['report'])): ?><div class="prose md-report"><?= $m['report'] /* sanitized on save */ ?></div><?php endif ?>
  </div></article>
<nav class="md-nav" aria-label="<?= e(t('More matches')) ?>">
  <?php if ($prev): ?><a class="md-nav-a" href="<?= e(url('matches/' . (int) $prev['id'])) ?>">← <span><?= e(t('Previous')) ?></span> <b>vs <?= e($prev['opponent']) ?></b></a><?php else: ?><span></span><?php endif ?>
  <?php if (can('admin.matches')): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/matches/' . (int) $m['id'])) ?>">✎ <?= e(t('Edit match')) ?></a><?php endif ?>
  <?php if ($nextM): ?><a class="md-nav-a md-nav-n" href="<?= e(url('matches/' . (int) $nextM['id'])) ?>"><span><?= e(t('Next')) ?></span> <b>vs <?= e($nextM['opponent']) ?></b> →</a><?php else: ?><span></span><?php endif ?>
</nav>
