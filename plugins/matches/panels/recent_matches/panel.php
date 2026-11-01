<?php
use MagicCMS\Plugins\Matches\Matches;
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'matches' AND enabled = 1")) return;
$rows = Matches::recent(Matches::panelCount());
$next = Matches::next();
if (!$rows && !$next) return; ?>
<?php if ($next): ?><a class="next-match" href="<?= e(url('matches/' . (int) $next['id'])) ?>"><span class="eyebrow"><?= e(t('Up next')) ?></span><strong><?= e(t('vs')) ?> <?= e($next['opponent']) ?></strong><span class="muted small"><?= e(fmt_date($next['played_at'], 'M j · H:i')) ?></span></a><?php endif ?>
<ul class="match-mini">
<?php foreach ($rows as $m): $res = Matches::result($m); ?>
  <li><a class="mm match-<?= e($res) ?>" href="<?= e(url('matches/' . (int) $m['id'])) ?>"><span class="mr-badge"><?= e(Matches::letter($res)) ?></span><span class="mm-opp clip"><?= e($m['opponent']) ?></span><span class="mm-score"><?= (int) $m['score_us'] ?>:<?= (int) $m['score_them'] ?></span><time class="muted small"><?= e(time_ago($m['played_at'])) ?></time></a></li>
<?php endforeach ?>
</ul>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('matches')) ?>"><?= e(t('All matches')) ?> →</a>
