<?php
/** @var array $settings */
use MagicCMS\Core\Emoji;
use MagicCMS\Plugins\Roster\Roster;
if (!db()->val("SELECT 1 FROM {modules} WHERE module_key = 'roster' AND enabled = 1")) return;
$d = Roster::panelData($settings);
if (!$d['groups']) return;
$chips = ($settings['view'] ?? 'list') === 'chips';
$photos = !empty($settings['show_photos']); $flags = !empty($settings['show_flags']); $counts = !empty($settings['show_counts']); $tags = !empty($settings['show_positions']);
$flag = static function (string $cc): string { $c = strtolower($cc); return preg_match('/^[a-z]{2}$/', $c) && isset(Emoji::flags()[$c]) ? Emoji::flagImg($c) : ''; };
?>
<div class="rp <?= $chips ? 'rp-chips' : 'rp-list' ?><?= ($settings['density'] ?? 'cozy') === 'compact' ? ' rp-tight' : '' ?>">
<?php $colors = Roster::roleColors();
foreach ($d['groups'] as $g): $hue = $g['name'] !== '' ? Roster::hue($g['name']) : 210; $cc = ($settings['accent'] ?? 'roles') === 'theme' ? 'var(--c-primary)' : ($colors[mb_strtolower($g['name'])] ?? ''); ?>
  <div class="rp-group" style="--h:<?= (int) $hue ?><?= $cc !== '' ? ';--c:' . e($cc) : '' ?>">
    <?php if ($g['name'] !== ''): ?><h4 class="rp-title"><span><?= e($g['name']) ?></span><?php if ($counts): ?><b><?= (int) $g['total'] ?></b><?php endif ?></h4><?php endif ?>
    <ul>
    <?php foreach ($g['members'] as $m): $ph = $photos ? upload_url('roster', $m['photo']) : null; $tip = $m['nickname'] . ($m['role'] !== '' ? ' – ' . $m['role'] : '') . ($m['position'] !== '' ? ' · ' . $m['position'] : ''); ?>
      <li><a class="rp-m" href="<?= e(url('roster/' . (int) $m['id'])) ?>" title="<?= e($tip) ?>">
        <?php if ($photos && !$chips): ?><?php if ($ph): ?><img class="rp-av" src="<?= e($ph) ?>" alt="" width="30" height="30" loading="lazy"><?php else: ?><span class="rp-av rp-ini"><?= e(Roster::initials($m['nickname'])) ?></span><?php endif ?><?php endif ?>
        <span class="rp-name"><?= e($m['nickname']) ?></span>
        <?php if ($tags && $m['position'] !== ''): ?><small class="rp-tag"><?= e($m['position']) ?></small><?php endif ?>
        <?php if ($flags && ($f = $flag((string) $m['country'])) !== ''): ?><span class="rp-flag"><?= $f ?></span><?php endif ?>
      </a></li>
    <?php endforeach ?>
    </ul>
  </div>
<?php endforeach ?>
</div>
<a class="btn btn-sm btn-ghost btn-block" href="<?= e(url('roster')) ?>"><?= $d['hidden'] > 0 ? e(t('Full roster (+{n} more)', ['n' => $d['hidden']])) : e(t('Full roster')) ?> →</a>
