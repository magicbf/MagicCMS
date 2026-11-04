<?php /** @var array $cfg @var array $roles @var array $counts @var array $colors @var array $vis */
use MagicCMS\Plugins\Roster\Roster;
$row = $cfg['row']; $vals = $cfg['values']; ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/roster')) ?>">← <?= e(t('Members')) ?></a> <a class="btn btn-ghost" href="<?= e(url('roster')) ?>"><?= e(t('Public page')) ?> →</a></p>
<form method="post" action="<?= e(url('admin/roster/settings')) ?>" data-roster-settings><?= csrf_field() ?>

<div class="card"><h2 class="card-title">🏷️ <?= e(t('Roles – order and colours')) ?></h2><div class="card-body">
  <p class="muted"><?= e(t('Roles are listed on the roster page and in the roster panel in this order. Give a role its own colour – it tints its heading, its pills and its block in the panel. Roles you do not list follow after these.')) ?></p>
  <ul class="rs-roles" id="rs-roles">
  <?php $i = 0; foreach ($roles as $k => $name): $hex = $colors[$k] ?? ''; ?>
    <li class="rs-role">
      <input type="hidden" name="roles[<?= $i ?>]" value="<?= e($name) ?>">
      <span class="rs-move"><button type="button" class="btn btn-sm btn-ghost" data-up aria-label="<?= e(t('Up')) ?>">▲</button><button type="button" class="btn btn-sm btn-ghost" data-down aria-label="<?= e(t('Down')) ?>">▼</button></span>
      <span class="rs-name"><span class="rs-dot" style="--c:<?= e($hex !== '' ? $hex : 'hsl(' . Roster::hue($name) . ' 70% 62%)') ?>"></span><strong><?= e($name) ?></strong> <small class="muted"><?= (int) ($counts[$k] ?? 0) ?> <?= e(t('member(s)')) ?></small></span>
      <label class="checkbox rs-own"><input type="checkbox" name="role_color_on[<?= $i ?>]" value="1" <?= $hex !== '' ? 'checked' : '' ?> data-color-on> <?= e(t('own colour')) ?></label>
      <input type="color" name="role_color[<?= $i ?>]" value="<?= e($hex !== '' ? $hex : sprintf('#%02x%02x%02x', ...Roster::hueRgb(Roster::hue($name)))) ?>" data-color aria-label="<?= e(t('Colour')) ?>">
    </li>
  <?php $i++; endforeach; if (!$roles): ?><li class="muted"><?= e(t('No roles yet – give your members a Role first, or add one below.')) ?></li><?php endif ?>
  </ul>
  <div class="inline-form"><input class="input" name="new_role" maxlength="60" placeholder="<?= e(t('Add a role (e.g. Coach)')) ?>"></div>
  <small class="help"><?= e(t('Without “own colour” a role uses an automatic colour in the panel and the theme colour on the roster page.')) ?></small>
</div></div>

<?php if (!$row): ?>
<div class="card"><h2 class="card-title">🛡️ <?= e(t('Roster panel')) ?></h2><div class="card-body"><p class="muted"><?= e(t('The roster panel has not been added yet. Add “Our Team” under Panels, then come back here to set it up.')) ?> <a href="<?= e(url('admin/panels')) ?>"><?= e(t('Open Panels')) ?> →</a></p></div></div>
<?php else: ?>
<div class="card"><h2 class="card-title">🛡️ <?= e(t('Roster panel – where and who')) ?></h2><div class="card-body">
  <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= $row['enabled'] ? 'checked' : '' ?>> <?= e(t('Show the roster panel on the site')) ?></label>
  <div class="form-row"><label class="label" for="rp-title"><?= e(t('Panel title')) ?></label><input class="input" id="rp-title" name="title" maxlength="120" value="<?= e($row['title']) ?>"></div>
  <div class="form-row"><label class="label" for="rp-pos"><?= e(t('Position')) ?></label><select class="select" id="rp-pos" name="position"><?php foreach (['left' => 'Left column', 'right' => 'Right column', 'above_footer' => 'Above the footer', 'footer' => 'In the footer'] as $pv => $pl): ?><option value="<?= e($pv) ?>" <?= $row['position'] === $pv ? 'selected' : '' ?>><?= e(t($pl)) ?></option><?php endforeach ?></select></div>
  <div class="form-row"><label class="label" for="rp-who"><?= e(t('Visible to')) ?></label><select class="select" id="rp-who" name="who"><?php foreach (['all' => 'Everyone', 'guests' => 'Guests only', 'members' => 'Logged-in members', 'staff' => 'Staff only'] as $pv => $pl): ?><option value="<?= e($pv) ?>" <?= ($vis['who'] ?? 'all') === $pv ? 'selected' : '' ?>><?= e(t($pl)) ?></option><?php endforeach ?></select></div>
  <div class="form-row"><span class="label"><?= e(t('Devices')) ?></span>
    <label class="checkbox"><input type="checkbox" name="desktop" value="1" <?= ($vis['desktop'] ?? true) ? 'checked' : '' ?>> <?= e(t('Desktop')) ?></label>
    <label class="checkbox"><input type="checkbox" name="tablet" value="1" <?= ($vis['tablet'] ?? true) ? 'checked' : '' ?>> <?= e(t('Tablet')) ?></label>
    <label class="checkbox"><input type="checkbox" name="mobile" value="1" <?= ($vis['mobile'] ?? true) ? 'checked' : '' ?>> <?= e(t('Phone')) ?></label></div>
</div></div>

<div class="card"><h2 class="card-title">⚙️ <?= e(t('Roster panel – what it shows')) ?></h2><div class="card-body">
<?php foreach ($cfg['schema'] as $name => $def): $val = $vals[$name] ?? ''; $type = $def['type'] ?? 'text'; ?>
  <div class="form-row"><?php if ($type === 'checkbox'): ?><label class="checkbox"><input type="checkbox" name="s[<?= e($name) ?>]" value="1" <?= $val ? 'checked' : '' ?>> <?= e(t($def['label'] ?? $name)) ?></label>
  <?php else: ?><label class="label" for="s_<?= e($name) ?>"><?= e(t($def['label'] ?? $name)) ?></label>
    <?php if ($type === 'select'): ?><select class="select" id="s_<?= e($name) ?>" name="s[<?= e($name) ?>]"><?php foreach ((array) ($def['options'] ?? []) as $ov => $ol): ?><option value="<?= e((string) $ov) ?>" <?= (string) $val === (string) $ov ? 'selected' : '' ?>><?= e(t((string) $ol)) ?></option><?php endforeach ?></select>
    <?php else: ?><input class="input" id="s_<?= e($name) ?>" type="<?= $type === 'number' ? 'number' : 'text' ?>"<?= $type === 'number' ? ' min="0" max="200"' : '' ?> name="s[<?= e($name) ?>]" value="<?= e((string) $val) ?>"><?php endif ?><?php endif ?>
    <?php if (!empty($def['help'])): ?><small class="help"><?= e(t($def['help'])) ?></small><?php endif ?></div>
<?php endforeach ?>
</div></div>
<?php endif ?>
<p><button class="btn btn-primary"><?= e(t('Save roster settings')) ?></button></p>
</form>
