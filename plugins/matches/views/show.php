<?php /** @var array $m */ use MagicCMS\Plugins\Matches\Matches; $res = Matches::result($m); ?>
<nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(url('matches')) ?>"><?= e(t('Matches')) ?></a> <span class="sep">›</span> <span>vs <?= e($m['opponent']) ?></span></nav>
<article class="card match-detail match-<?= e($res ?? 'upcoming') ?>"><h1 class="card-title"><?= e($m['competition'] ?: t('Match')) ?></h1>
  <div class="card-body">
    <div class="md-versus"><div class="md-team"><span class="md-logo"><?= e(mb_strtoupper(mb_substr((string) setting('site.name', 'US'), 0, 3))) ?></span><strong><?= e(setting('site.name')) ?></strong></div>
      <div class="md-score"><?= $res ? (int) $m['score_us'] . '<i>:</i>' . (int) $m['score_them'] : 'VS' ?><span class="mr-badge"><?= e($res ? t(ucfirst($res)) : t('Upcoming')) ?></span></div>
      <div class="md-team"><span class="md-logo alt"><?= e($m['opponent_tag'] !== '' ? $m['opponent_tag'] : mb_strtoupper(mb_substr($m['opponent'], 0, 3))) ?></span><strong><?= e($m['opponent']) ?></strong></div></div>
    <dl class="md-facts"><div><dt><?= e(t('Date')) ?></dt><dd><?= e(fmt_date($m['played_at'], 'Y-m-d H:i')) ?></dd></div>
      <?php if ($m['game'] !== ''): ?><div><dt><?= e(t('Game')) ?></dt><dd><?= e($m['game']) ?></dd></div><?php endif ?>
      <?php if ($m['map'] !== ''): ?><div><dt><?= e(t('Map')) ?></dt><dd><?= e($m['map']) ?></dd></div><?php endif ?></dl>
    <?php if ($m['vod_url'] !== ''): ?><p><a class="btn btn-primary btn-sm" href="<?= e($m['vod_url']) ?>" target="_blank" rel="noopener noreferrer">▶ <?= e(t('Watch the VOD')) ?></a></p><?php endif ?>
    <?php if (!empty($m['report'])): ?><div class="prose"><?= $m['report'] /* sanitized on save */ ?></div><?php endif ?>
  </div></article>
<?php if (can('admin.matches')): ?><p><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/matches/' . (int) $m['id'])) ?>">✎ <?= e(t('Edit match')) ?></a></p><?php endif ?>
