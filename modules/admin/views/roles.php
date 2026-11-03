<?php /** @var array $roles @var array $members */
use MagicCMS\Core\DisplayRoles as DR;
$presets = ['#7ae24b', '#ffc94a', '#ff5b6b', '#6cb8ff', '#b07cff', '#ff7ac8', '#35d4c4', '#ff8a3d', '#c0c6d4'];
$icons = ['👑', '⭐', '🏆', '🎮', '🎥', '🛡️', '⚔️', '💎', '🔥', '🎙️', '🧠', '💬'];
$new = ['id' => 0, 'name' => '', 'color' => '#7ae24b', 'icon' => '', 'style' => 'soft', 'places' => 'forum,shoutbox,profile', 'sort_order' => 0, 'enabled' => 1];
$form = static function (array $r, array $mem) use ($presets, $icons): void { $isNew = !$r['id']; $pl = explode(',', (string) $r['places']); ?>
<form class="card role-card" method="post" action="<?= e(url('admin/roles/save')) ?>" data-role-form><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
  <h2 class="card-title"><span class="role-preview"><?= DR::chip($r['name'] !== '' ? $r : ['name' => t('New role'), 'color' => $r['color'], 'icon' => '', 'style' => $r['style']]) ?></span>
    <?php if (!$isNew): ?><small><?= count($mem) ?> <?= e(t('member(s)')) ?><?= $r['enabled'] ? '' : ' · ' . e(t('off')) ?></small><?php endif ?></h2>
  <div class="card-body">
    <div class="form-grid role-grid">
      <div class="form-row"><label class="label"><?= e(t('Name')) ?></label><input class="input" name="name" maxlength="40" value="<?= e($r['name']) ?>" placeholder="<?= e(t('e.g. Captain')) ?>" required data-r="name"></div>
      <div class="form-row"><label class="label"><?= e(t('Icon (optional)')) ?></label><input class="input" name="icon" maxlength="8" value="<?= e($r['icon']) ?>" list="role-icons-<?= (int) $r['id'] ?>" placeholder="👑" data-r="icon"><datalist id="role-icons-<?= (int) $r['id'] ?>"><?php foreach ($icons as $i): ?><option value="<?= e($i) ?>"><?php endforeach ?></datalist></div>
      <div class="form-row"><label class="label"><?= e(t('Colour')) ?></label>
        <div class="role-colors"><input type="color" name="color" value="<?= e($r['color']) ?>" data-r="color"><?php foreach ($presets as $c): ?><button type="button" class="rc-dot" style="--c:<?= e($c) ?>" data-color="<?= e($c) ?>" aria-label="<?= e($c) ?>"></button><?php endforeach ?></div></div>
      <div class="form-row"><label class="label"><?= e(t('Style')) ?></label><select class="select" name="style" data-r="style"><?php foreach (DR::STYLES as $k => $l): ?><option value="<?= e($k) ?>" <?= $r['style'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
      <div class="form-row"><label class="label"><?= e(t('Order')) ?></label><input class="input" type="number" min="0" max="9999" name="sort_order" value="<?= (int) $r['sort_order'] ?>"><small class="help"><?= e(t('Lower numbers are shown first.')) ?></small></div>
    </div>
    <fieldset class="devs places" title="<?= e(t('Where this role is shown')) ?>"><legend><?= e(t('Show in')) ?></legend>
      <?php foreach (DR::PLACES as $k => $l): ?><label class="dev"><input type="checkbox" name="places[]" value="<?= e($k) ?>" <?= in_array($k, $pl, true) ? 'checked' : '' ?>><span><?= e(t($l)) ?></span></label><?php endforeach ?>
    </fieldset>
    <div class="role-actions">
      <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= $r['enabled'] ? 'checked' : '' ?>> <?= e(t('Enabled')) ?></label>
      <button class="btn btn-primary btn-sm"><?= e($isNew ? t('Create role') : t('Save')) ?></button>
      <?php if (!$isNew): ?><button class="btn btn-danger btn-sm" formaction="<?= e(url('admin/roles/' . (int) $r['id'] . '/delete')) ?>" formnovalidate data-confirm="<?= e(t('Delete this role? It will be removed from all members.')) ?>">✕ <?= e(t('Delete')) ?></button><?php endif ?>
    </div>
    <?php if (!$isNew): ?>
    <div class="role-members">
      <h3><?= e(t('Members with this role')) ?></h3>
      <?php if ($mem): ?><ul class="rm-list"><?php foreach ($mem as $m): ?><li><a href="<?= e(url('user/' . $m['username'])) ?>"><?= e($m['username']) ?></a><button class="rm-x" formaction="<?= e(url('admin/roles/' . (int) $r['id'] . '/unassign')) ?>" name="uid" value="<?= (int) $m['id'] ?>" formnovalidate aria-label="<?= e(t('Remove')) ?>">✕</button></li><?php endforeach ?></ul>
      <?php else: ?><p class="muted"><?= e(t('Nobody has this role yet.')) ?></p><?php endif ?>
      <div class="rm-add"><input class="input" name="usernames" placeholder="<?= e(t('Add members by username (separate several with commas)')) ?>" aria-label="<?= e(t('Usernames')) ?>"><button class="btn btn-sm" formaction="<?= e(url('admin/roles/' . (int) $r['id'] . '/assign')) ?>" formnovalidate>＋ <?= e(t('Add')) ?></button></div>
    </div>
    <?php endif ?>
  </div>
</form>
<?php }; ?>
<p class="muted"><?= e(t('Roles are titles with a colour – like Captain, Streamer or Founder. They are shown next to a member’s name and are separate from permission levels (Member, Moderator, Admin …). A member can have several. Choose for each role where it appears.')) ?></p>

<form class="card" method="post" action="<?= e(url('admin/roles/settings')) ?>"><?= csrf_field() ?>
  <div class="card-body role-settings">
    <label class="label" for="ms"><?= e(t('Show at most this many roles per member in one place')) ?></label>
    <select class="select" id="ms" name="max_shown"><?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>" <?= DR::max() === $i ? 'selected' : '' ?>><?= $i ?></option><?php endfor ?></select>
    <button class="btn btn-sm"><?= e(t('Save')) ?></button>
  </div>
</form>

<h3 class="section-title"><?= e(t('New role')) ?></h3>
<?php $form($new, []); ?>

<?php if ($roles): ?><h3 class="section-title"><?= e(t('Your roles')) ?></h3><?php endif ?>
<?php foreach ($roles as $r): $form($r, $members[(int) $r['id']] ?? []); endforeach ?>
