<?php /** @var array $m */ use MagicCMS\Plugins\Matches\Matches; $res = Matches::result($m); ?>
<a class="match-row match-<?= e($res ?? 'upcoming') ?>" href="<?= e(url('matches/' . (int) $m['id'])) ?>">
  <span class="mr-badge" aria-label="<?= e($res ? t(ucfirst($res)) : t('Upcoming')) ?>"><?= e(Matches::letter($res)) ?></span>
  <span class="mr-main"><strong class="mr-opp"><?= e(t('vs')) ?> <?= e($m['opponent']) ?></strong><?php if ($m['opponent_tag'] !== ''): ?> <span class="badge"><?= e($m['opponent_tag']) ?></span><?php endif ?>
    <span class="muted small mr-sub"><?= e(trim($m['competition'] . ($m['map'] !== '' ? ' · ' . $m['map'] : ''), ' ·')) ?></span></span>
  <span class="mr-score"><?= $res ? (int) $m['score_us'] . '<i>:</i>' . (int) $m['score_them'] : '–' ?></span>
  <span class="mr-date muted small"><?= e(fmt_date($m['played_at'], 'M j, Y')) ?><br><?= e(fmt_date($m['played_at'], 'H:i')) ?></span>
</a>
