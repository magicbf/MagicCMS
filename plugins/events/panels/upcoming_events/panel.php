<?php
/** @var array $settings */
use MagicCMS\Plugins\Events\Events;
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'events' AND enabled = 1")) return;
$rows = db()->all("SELECT * FROM {events} WHERE status = 'published' AND COALESCE(ends_at, DATE_ADD(starts_at, INTERVAL 2 HOUR)) >= ? ORDER BY starts_at LIMIT " . max(1, min(12, (int) $settings['count'])), [now()]);
if (!$rows) return; $tz = Events::tz(); ?>
<ul class="ev-mini">
<?php foreach ($rows as $e): [$lab, $ic, $col] = Events::TYPES[$e['type']]; $d = (new DateTimeImmutable($e['starts_at'], new DateTimeZone('UTC')))->setTimezone($tz); ?>
  <li style="--ev-color:<?= e($col) ?>"><a class="ev-mini-date" href="<?= e(url('events/' . (int) $e['id'])) ?>" tabindex="-1" aria-hidden="true"><b><?= e($d->format('j')) ?></b><span><?= e($d->format('M')) ?></span></a>
    <div><a class="nm-title" href="<?= e(url('events/' . (int) $e['id'])) ?>"><?= e($ic . ' ' . $e['title']) ?></a><span class="muted small"><?= e($d->format('D · H:i')) ?></span></div></li>
<?php endforeach ?>
</ul>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('events')) ?>"><?= e(t('All events')) ?> →</a>
