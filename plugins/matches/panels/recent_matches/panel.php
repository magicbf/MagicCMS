<?php
use MagicCMS\Plugins\Matches\Matches;
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'matches' AND enabled = 1")) return;
$rows = Matches::recent(Matches::panelCount());
$next = Matches::next();
if (!$rows && !$next) return;
$st = Matches::stats();
$form = array_reverse(Matches::recent(5));   // oldest → newest, so the newest result sits at the right end
$crest = static fn(string $tag, string $name): string => mb_strtoupper(mb_substr(trim($tag) !== '' ? $tag : preg_replace('/[^\p{L}\p{N}]+/u', '', $name), 0, 3));
$streakRes = $st['streak'] !== '–' ? $st['streak'][0] : '';
$streakN = (int) substr($st['streak'], 1);
$countdown = '';
if ($next) {
    $sec = strtotime($next['played_at'] . ' UTC') - time();
    $countdown = $sec < 3600 ? t('starting soon') : ($sec < 86400 ? t('in {n} h', ['n' => (int) round($sec / 3600)]) : t('in {n} d', ['n' => (int) floor($sec / 86400)]) . ($sec % 86400 >= 3600 ? ' ' . t('{n} h', ['n' => intdiv($sec % 86400, 3600)]) : ''));
}
$sub = static function (array $m): string {
    $bits = array_filter([trim((string) ($m['competition'] ?? '')), trim((string) ($m['map'] ?? ''))], 'strlen');
    return implode(' · ', $bits);
};
?>
<div class="mp">
<?php if ($st['played']): ?>
  <div class="mp-record" role="group" aria-label="<?= e(t('Season record')) ?>">
    <div class="mp-ring" style="--p:<?= (int) $st['rate'] ?>" role="img" aria-label="<?= e(t('Win rate')) ?> <?= (int) $st['rate'] ?>%"><b><?= (int) $st['rate'] ?><small>%</small></b><span><?= e(t('win rate')) ?></span></div>
    <div class="mp-rec">
      <div class="mp-wld"><span class="w"><b><?= (int) $st['wins'] ?></b><?= e(t('W')) ?></span><span class="d"><b><?= (int) $st['draws'] ?></b><?= e(t('D')) ?></span><span class="l"><b><?= (int) $st['losses'] ?></b><?= e(t('L')) ?></span></div>
      <div class="mp-bar" aria-hidden="true"><i class="w" style="flex-grow:<?= (int) $st['wins'] ?>"></i><i class="d" style="flex-grow:<?= (int) $st['draws'] ?>"></i><i class="l" style="flex-grow:<?= (int) $st['losses'] ?>"></i></div>
      <div class="mp-formrow"><span class="mp-form" aria-label="<?= e(t('Recent form')) ?>"><?php foreach ($form as $m): $r = Matches::result($m); ?><i class="<?= e(strtolower(Matches::letter($r))) ?>" title="<?= e($m['opponent']) ?> <?= (int) $m['score_us'] ?>:<?= (int) $m['score_them'] ?>"><?= e(Matches::letter($r)) ?></i><?php endforeach ?></span>
      <?php if ($streakN >= 2 && $streakRes !== 'D'): ?><span class="mp-streak <?= $streakRes === 'W' ? 'w' : 'l' ?>"><?= $streakRes === 'W' ? '🔥' : '❄️' ?> <?= e($st['streak']) ?></span><?php endif ?></div>
    </div>
  </div>
<?php endif ?>
<?php if ($next): ?>
  <a class="next-match mp-next" href="<?= e(url('matches/' . (int) $next['id'])) ?>">
    <span class="mp-crest mp-crest-next" aria-hidden="true"><?= e($crest((string) $next['opponent_tag'], (string) $next['opponent'])) ?></span>
    <span class="mp-next-body"><span class="eyebrow"><?= e(t('Up next')) ?></span><strong><?= e(t('vs')) ?> <?= e($next['opponent']) ?></strong>
      <span class="muted small"><?= e(fmt_date($next['played_at'], 'M j · H:i')) ?><?= $sub($next) !== '' ? ' · ' . e($sub($next)) : '' ?></span></span>
    <span class="mp-count"><?= e($countdown) ?></span>
  </a>
<?php endif ?>
<?php if ($rows): ?>
<ul class="match-mini mp-list">
<?php foreach ($rows as $m): $res = Matches::result($m); $win = $res === 'win'; $loss = $res === 'loss'; ?>
  <li><a class="mm match-<?= e($res) ?> mp-row" href="<?= e(url('matches/' . (int) $m['id'])) ?>" title="<?= e(t($res === 'win' ? 'Win' : ($res === 'loss' ? 'Loss' : 'Draw'))) ?>">
    <span class="mp-crest" aria-hidden="true"><?= e($crest((string) $m['opponent_tag'], (string) $m['opponent'])) ?></span>
    <span class="mp-info"><span class="mm-opp clip"><?= e($m['opponent']) ?></span><span class="mp-sub clip"><?= e($sub($m) !== '' ? $sub($m) . ' · ' : '') ?><time><?= e(time_ago($m['played_at'])) ?></time></span></span>
    <span class="mm-score mp-score"><b class="<?= $win ? 'hi' : '' ?>"><?= (int) $m['score_us'] ?></b><i>:</i><b class="<?= $loss ? 'hi' : '' ?>"><?= (int) $m['score_them'] ?></b><em class="mr-badge"><?= e(Matches::letter($res)) ?></em></span>
  </a></li>
<?php endforeach ?>
</ul>
<?php endif ?>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('matches')) ?>"><?= e(t('All matches')) ?> →</a>
</div>
