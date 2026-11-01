<?php /** @var array $e @var array $counts @var array $people @var string $state @var ?string $mine @var ?array $bracket */ use MagicCMS\Plugins\Events\{Bracket, Events}; [$label, $icon, $color] = Events::TYPES[$e['type']]; $tz = Events::tz();
$s = (new DateTimeImmutable($e['starts_at'], new DateTimeZone('UTC')))->setTimezone($tz); $en = (new DateTimeImmutable(Events::end($e), new DateTimeZone('UTC')))->setTimezone($tz); $over = Events::isOver($e);
$diff = strtotime($e['starts_at'] . ' UTC') - time(); ?>
<nav class="breadcrumb"><a href="<?= e(url('events')) ?>"><?= e(t('Events')) ?></a> <span class="sep">›</span> <span><?= e($e['title']) ?></span></nav>
<article class="card ev-detail" style="--ev-color:<?= e($color) ?>"><h1 class="card-title"><?= e($icon . ' ' . $e['title']) ?></h1><div class="card-body">
  <div class="ev-facts">
    <div><dt><?= e(t('When')) ?></dt><dd><?= e($s->format('D, M j Y · H:i')) ?> – <?= e($s->format('Ymd') === $en->format('Ymd') ? $en->format('H:i') : $en->format('M j · H:i')) ?> <small class="muted"><?= e($tz->getName()) ?></small></dd></div>
    <?php if ($e['location'] !== ''): ?><div><dt><?= e(t('Where')) ?></dt><dd><?= e($e['location']) ?></dd></div><?php endif ?>
    <div><dt><?= e(t('Type')) ?></dt><dd><span class="cat-badge" style="--cat-color:<?= e($color) ?>"><?= e(t($label)) ?></span><?php if ($e['status'] === 'draft'): ?> <span class="badge"><?= e(t('Draft')) ?></span><?php endif ?></dd></div>
    <div><dt><?= e($over ? t('Status') : t('Starts')) ?></dt><dd><?= e($over ? t('Finished') : ($diff < 3600 && $diff > 0 ? t('in {n} min', ['n' => max(1, intdiv($diff, 60))]) : ($diff < 172800 ? t('in {n} h', ['n' => intdiv($diff, 3600)]) : t('in {n} days', ['n' => (int) ceil($diff / 86400)])))) ?></dd></div>
  </div>
  <?php if (!empty($e['description'])): ?><div class="prose"><?= $e['description'] /* sanitized on save */ ?></div><?php endif ?>
  <p class="inline-form mt"><a class="btn btn-ghost btn-sm" href="<?= e(url('events/' . (int) $e['id'] . '.ics')) ?>">📅 <?= e(t('Add to calendar')) ?></a>
    <?php if ($e['url'] !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e($e['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e(t('Event link')) ?> ↗</a><?php endif ?></p>
</div></article>

<section class="card"><h2 class="card-title"><?= e(t('Sign-up')) ?> <small>✔ <?= (int) $counts['going'] ?><?= $e['max_participants'] !== null ? ' / ' . (int) $e['max_participants'] : '' ?></small></h2><div class="card-body">
  <?php if ($state === 'over'): ?><p class="muted"><?= e(t('This event is over.')) ?></p>
  <?php elseif ($state === 'closed'): ?><p class="muted"><?= e(t('Sign-up is closed for this event.')) ?></p>
  <?php elseif (!user()): ?><p><a class="btn btn-primary" href="<?= e(url('login?next=' . rawurlencode('/events/' . (int) $e['id']))) ?>"><?= e(t('Log in to sign up')) ?></a></p>
  <?php elseif (!can('events.signup')): ?><p class="muted"><?= e(t('Your account may not sign up for events.')) ?></p>
  <?php else: ?>
    <?php if ($mine): ?><p class="ev-mine">✅ <?= e(t('Your status')) ?>: <strong><?= e(['going' => t('Going'), 'maybe' => t('Maybe'), 'no' => t('Not going'), 'waitlist' => t('On the waiting list')][$mine]) ?></strong></p><?php endif ?>
    <form class="inline-form" method="post" action="<?= e(url('events/' . (int) $e['id'] . '/signup')) ?>"><?= csrf_field() ?>
      <button class="btn <?= $mine === 'going' ? 'btn-primary' : 'btn-ghost' ?>" name="status" value="going"><?= e($state === 'full' && $mine !== 'going' ? t('Join the waiting list') : t('I\'m going')) ?></button>
      <button class="btn <?= $mine === 'maybe' ? 'btn-primary' : 'btn-ghost' ?>" name="status" value="maybe"><?= e(t('Maybe')) ?></button>
      <button class="btn <?= $mine === 'no' ? 'btn-primary' : 'btn-ghost' ?>" name="status" value="no"><?= e(t('Can\'t make it')) ?></button></form>
    <?php if ($state === 'full' && $mine !== 'going'): ?><p class="muted small mt"><?= e(t('All places are taken. Join the waiting list and move up automatically if someone drops out.')) ?></p><?php endif ?>
  <?php endif ?>
  <?php if ($people): ?><div class="ev-people"><?php foreach (['going' => 'Going', 'waitlist' => 'Waiting list', 'maybe' => 'Maybe'] as $st => $lab): $grp = array_filter($people, fn($p) => $p['status'] === $st); if (!$grp) continue; ?>
    <h3 class="section-title small"><?= e(t($lab)) ?> (<?= count($grp) ?>)</h3><div class="ev-avatars"><?php foreach ($grp as $p): ?><a href="<?= e(url('user/' . $p['username'])) ?>" title="<?= e($p['username']) ?>" class="ev-av"><img class="avatar avatar-md" src="<?= e(avatar_url($p, 64)) ?>" alt="<?= e($p['username']) ?>" width="44" height="44" loading="lazy"></a><?php endforeach ?></div><?php endforeach ?></div><?php endif ?>
</div></section>

<?php if ($bracket): $rounds = $bracket['rounds']; $total = count($rounds); $champ = Bracket::champion($bracket); ?>
<section class="card" id="bracket"><h2 class="card-title">🏆 <?= e(t('Bracket')) ?></h2><div class="card-body"><div class="bracket" role="group" aria-label="<?= e(t('Tournament bracket')) ?>">
  <?php foreach ($rounds as $ri => $round): ?><div class="b-round"><h3 class="b-title"><?= e(Bracket::roundName($ri, $total)) ?></h3><div class="b-matches">
    <?php foreach ($round as $m): $w = Bracket::winner($m); ?><div class="b-match"><?php foreach (['a', 'b'] as $side): $name = $m[$side]; $bye = $name === null && ($m['a'] === null ? $m['b'] : $m['a']) !== null && $ri === 0; ?>
      <div class="b-team<?= $w === $side ? ' is-win' : ($w ? ' is-lose' : '') ?><?= $name === null ? ' is-tbd' : '' ?>"><span class="b-name"><?= $name !== null ? e($name) : ($bye ? 'BYE' : e(t('TBD'))) ?></span><span class="b-score"><?= $m['s' . $side] !== null ? (int) $m['s' . $side] : '' ?></span></div><?php endforeach ?></div><?php endforeach ?></div></div><?php endforeach ?>
  <div class="b-round b-champ"><h3 class="b-title"><?= e(t('Champion')) ?></h3><div class="b-matches"><div class="b-trophy"><span>🏆</span><strong><?= $champ !== null ? e($champ) : e(t('TBD')) ?></strong></div></div></div>
</div></div></section><?php endif ?>
<?php if (can('admin.events')): ?><p><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/events/' . (int) $e['id'])) ?>">✎ <?= e(t('Edit event')) ?></a></p><?php endif ?>
