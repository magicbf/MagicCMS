<?php /** @var array $cfg @var array $slides @var bool $newsOn */ use MagicCMS\Core\Hero;
$sel = function (string $name) use ($cfg) { $o = '<select class="select" name="' . $name . '">'; foreach (Hero::OPTIONS[$name] as $k => $l) { $o .= '<option value="' . $k . '"' . ($cfg[$name] === $k ? ' selected' : '') . '>' . e(t($l)) . '</option>'; } return $o . '</select>'; };
$chk = fn(string $k, string $label) => '<label class="checkbox"><input type="checkbox" name="' . $k . '" value="1"' . ($cfg[$k] ? ' checked' : '') . '> ' . e(t($label)) . '</label>'; ?>
<p class="muted"><?= e(t('A banner at the top of the home page: a slider of slides with call-to-action buttons, optional pictures and animated effects.')) ?> <a href="<?= e(url('')) ?>"><?= e(t('Preview on the home page')) ?> →</a></p>
<form class="card" method="post" action="<?= e(url('admin/hero/settings')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Banner settings')) ?></h2><div class="card-body">
  <?= $chk('enabled', 'Show the hero banner') ?>
  <div class="form-grid mt">
    <div class="form-row"><label class="label"><?= e(t('Where to show it')) ?></label><?= $sel('placement') ?></div>
    <div class="form-row"><label class="label"><?= e(t('Content')) ?></label><?= $sel('source') ?><?php if (!$newsOn): ?><small class="help"><?= e(t('The News module is disabled.')) ?></small><?php endif ?></div>
    <div class="form-row"><label class="label"><?= e(t('Height')) ?></label><?= $sel('height') ?></div>
    <div class="form-row"><label class="label"><?= e(t('Width')) ?></label><?= $sel('width') ?></div>
    <div class="form-row"><label class="label"><?= e(t('Transition')) ?></label><?= $sel('transition') ?></div>
    <div class="form-row"><label class="label"><?= e(t('Background effect')) ?></label><?= $sel('effect') ?></div>
    <div class="form-row"><label class="label"><?= e(t('Seconds per slide')) ?> (3–30)</label><input class="input" type="number" min="3" max="30" name="interval" value="<?= (int) $cfg['interval'] ?>"></div>
  </div>
  <div class="check-grid"><?= $chk('autoplay', 'Autoplay') ?><?= $chk('pause', 'Pause on hover / focus') ?><?= $chk('arrows', 'Show arrows') ?><?= $chk('dots', 'Show dots') ?><?= $chk('parallax', 'Mouse parallax (desktop)') ?></div>
  <p class="muted small"><?= e(t('Animations respect the visitor\'s "reduce motion" setting and Admin → Appearance → Animations.')) ?></p>
  <button class="btn btn-primary"><?= e(t('Save settings')) ?></button>
</div></form>

<div class="forum-head"><h2 class="section-title"><?= e(t('Slides')) ?></h2><a class="btn btn-primary btn-sm" href="<?= e(url('admin/hero/slide/new')) ?>">+ <?= e(t('New slide')) ?></a></div>
<div class="card"><div class="card-body table-wrap"><table class="table"><thead><tr><th></th><th><?= e(t('Slide')) ?></th><th><?= e(t('Buttons')) ?></th><th><?= e(t('Schedule')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($slides as $s): $img = upload_url('hero', $s['image']); ?>
<tr class="<?= $s['enabled'] ? '' : 'is-off' ?>"><td><?php if ($img): ?><img class="slide-thumb" src="<?= e($img) ?>" alt="" width="96" height="48" loading="lazy"><?php else: ?><span class="slide-thumb slide-thumb-grad"></span><?php endif ?></td>
<td><strong><?= e($s['headline'] ?: '—') ?></strong><div class="muted small"><?= e($s['eyebrow']) ?> · <?= e($s['align']) ?></div></td>
<td class="small"><?= e(trim($s['btn1_label'] . ' / ' . $s['btn2_label'], ' /')) ?: '—' ?></td>
<td class="small"><?= $s['starts_at'] || $s['ends_at'] ? e(($s['starts_at'] ? fmt_date($s['starts_at'], 'Y-m-d H:i') : '…') . ' → ' . ($s['ends_at'] ? fmt_date($s['ends_at'], 'Y-m-d H:i') : '…')) : '—' ?></td>
<td class="actions">
  <form method="post" action="<?= e(url('admin/hero/slide/move/' . (int) $s['id'] . '/up')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Up">▲</button></form>
  <form method="post" action="<?= e(url('admin/hero/slide/move/' . (int) $s['id'] . '/down')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" aria-label="Down">▼</button></form>
  <form method="post" action="<?= e(url('admin/hero/slide/toggle/' . (int) $s['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm <?= $s['enabled'] ? 'btn-ghost' : 'btn-primary' ?>"><?= e($s['enabled'] ? t('On') : t('Off')) ?></button></form>
  <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/hero/slide/' . (int) $s['id'])) ?>"><?= e(t('Edit')) ?></a>
  <form method="post" action="<?= e(url('admin/hero/slide/delete/' . (int) $s['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this slide?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$slides): ?><tr><td colspan="5" class="muted"><?= e(t('No slides yet.')) ?></td></tr><?php endif ?></tbody></table></div></div>
