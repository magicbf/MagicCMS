<?php
/** @var array $settings */
use MagicCMS\Plugins\Roster\Roster;
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'roster' AND enabled = 1")) return;
$sq = trim((string) $settings['squad']);
$rows = db()->all('SELECT * FROM {roster_members} WHERE is_active = 1' . ($sq !== '' ? ' AND squad = ?' : '') . ' ORDER BY sort_order, nickname LIMIT ' . max(1, min(24, (int) $settings['count'])), $sq !== '' ? [$sq] : []);
if (!$rows) return; ?>
<div class="rm-mini">
<?php foreach ($rows as $m): $ph = upload_url('roster', $m['photo']); ?>
  <a class="rm-mini-item" href="<?= e(url('roster/' . (int) $m['id'])) ?>" title="<?= e($m['nickname'] . ($m['role'] !== '' ? ' – ' . $m['role'] : '')) ?>"><?php if ($ph): ?><img src="<?= e($ph) ?>" alt="" width="64" height="80" loading="lazy"><?php else: ?><span class="rm-ph sm"><?= e(Roster::initials($m['nickname'])) ?></span><?php endif ?><span><?= e($m['nickname']) ?></span></a>
<?php endforeach ?>
</div>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('roster')) ?>"><?= e(t('Full roster')) ?> →</a>
