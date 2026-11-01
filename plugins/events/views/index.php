<?php /** @var string $view */ use MagicCMS\Plugins\Events\Events; ?>
<div class="page-intro"><div><span class="eyebrow"><?= e(t('Calendar')) ?></span><h1 class="page-title"><?= e(t('Events')) ?></h1></div>
  <a class="btn btn-ghost btn-sm" href="<?= e(url('events/feed.ics')) ?>" title="<?= e(t('Subscribe in your calendar app')) ?>">📅 <?= e(t('Subscribe (.ics)')) ?></a></div>
<div class="chip-row" role="tablist"><?php foreach (['upcoming' => 'Upcoming', 'calendar' => 'Calendar', 'past' => 'Past'] as $k => $l): ?><a class="chip<?= $view === $k ? ' is-active' : '' ?>" href="<?= e(url('events?view=' . $k)) ?>"><?= e(t($l)) ?></a><?php endforeach ?>
  <?php foreach (Events::TYPES as $k => [$tl, $ti, $tc]): ?><span class="chip chip-static" style="--cat-color:<?= e($tc) ?>"><i class="chip-dot"></i><?= e($ti . ' ' . t($tl)) ?></span><?php endforeach ?></div>
<?php if ($view === 'calendar'):
  /** @var DateTimeImmutable $month @var DateTimeImmutable $gridStart @var array $byDay @var string $today */
  $prev = $month->modify('-1 month')->format('Y-m'); $next = $month->modify('+1 month')->format('Y-m'); ?>
<section class="card"><h2 class="card-title"><a class="cat-link" href="<?= e(url('events?view=calendar&m=' . $prev)) ?>" aria-label="<?= e(t('Previous month')) ?>">‹</a><span class="cal-title"><?= e($month->format('F Y')) ?></span><a class="cat-link" href="<?= e(url('events?view=calendar&m=' . $next)) ?>" aria-label="<?= e(t('Next month')) ?>">›</a></h2>
  <div class="cal-grid" role="grid"><?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dn): ?><div class="cal-dow" role="columnheader"><?= e(t($dn)) ?></div><?php endforeach ?>
  <?php for ($i = 0; $i < 42; $i++): $day = $gridStart->modify("+$i days"); $key = $day->format('Y-m-d'); $other = $day->format('m') !== $month->format('m'); ?>
    <div class="cal-day<?= $other ? ' is-other' : '' ?><?= $key === $today ? ' is-today' : '' ?>" role="gridcell"><span class="cal-n"><?= e($day->format('j')) ?></span>
      <?php foreach ($byDay[$key] ?? [] as $e): [, $ic, $col] = Events::TYPES[$e['type']]; ?><a class="cal-ev" style="--ev-color:<?= e($col) ?>" href="<?= e(url('events/' . (int) $e['id'])) ?>" title="<?= e($e['title']) ?>"><?= e($ic) ?> <span><?= e($e['title']) ?></span></a><?php endforeach ?></div>
  <?php endfor ?></div></section>
<?php else: /** @var array $rows @var int $page @var int $pages */ ?>
<div class="ev-list"><?php foreach ($rows as $e): echo render('events/_card', ['e' => $e]); endforeach; if (!$rows): ?><div class="card"><div class="card-body muted"><?= e($view === 'past' ? t('No past events.') : t('No upcoming events right now.')) ?></div></div><?php endif ?></div>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('events?view=' . $view)]) ?>
<?php endif ?>
