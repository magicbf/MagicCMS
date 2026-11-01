<?php /** @var array $e event with counts */ use MagicCMS\Plugins\Events\Events; [$label, $icon, $color] = Events::TYPES[$e['type']]; $tz = Events::tz();
$d = (new DateTimeImmutable($e['starts_at'], new DateTimeZone('UTC')))->setTimezone($tz); $over = Events::isOver($e); $state = Events::signupState($e, $e['counts']); ?>
<article class="ev-card<?= $over ? ' is-over' : '' ?>" style="--ev-color:<?= e($color) ?>">
  <a class="ev-date" href="<?= e(url('events/' . (int) $e['id'])) ?>" aria-hidden="true" tabindex="-1"><b><?= e($d->format('j')) ?></b><span><?= e($d->format('M')) ?></span></a>
  <div class="ev-main">
    <div class="news-meta"><span class="cat-badge" style="--cat-color:<?= e($color) ?>"><?= e($icon . ' ' . t($label)) ?></span><?php if ($e['status'] === 'draft'): ?><span class="badge"><?= e(t('Draft')) ?></span><?php endif ?><span><?= e($d->format('D · H:i')) ?><?= $e['location'] !== '' ? ' · ' . e($e['location']) : '' ?></span></div>
    <h3 class="news-title"><a href="<?= e(url('events/' . (int) $e['id'])) ?>"><?= e($e['title']) ?></a></h3>
    <div class="ev-foot muted small"><span>✔ <?= (int) $e['counts']['going'] ?><?= $e['max_participants'] !== null ? ' / ' . (int) $e['max_participants'] : '' ?> <?= e(t('going')) ?></span>
      <?php if ($e['counts']['waitlist']): ?><span>⏳ <?= (int) $e['counts']['waitlist'] ?></span><?php endif ?>
      <?php if (!$over && $e['signup_open']): ?><span class="ev-state ev-<?= e($state) ?>"><?= e($state === 'full' ? t('Full – waitlist') : ($state === 'open' ? t('Sign-up open') : t('Sign-up closed'))) ?></span><?php endif ?></div>
  </div>
</article>
