<?php /** @var ?array $row @var array $squads @var string $linked */ use MagicCMS\Plugins\Roster\Roster;
$r = $row ?? ['id' => 0, 'nickname' => '', 'real_name' => '', 'role' => '', 'squad' => $squads[0] ?? 'Main roster', 'country' => '', 'bio' => '', 'photo' => null, 'joined_on' => gmdate('Y-m-d'), 'is_active' => 1, 'sort_order' => 0, 'socials_a' => [], 'stats_a' => []];
$ph = upload_url('roster', $r['photo']); $stats = array_pad($r['stats_a'], 6, ['label' => '', 'value' => '']); ?>
<form class="card" method="post" action="<?= e(url('admin/roster/save')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><h2 class="card-title"><?= e($r['id'] ? t('Edit member') : t('New member')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Nickname')) ?></label><input class="input" name="nickname" maxlength="40" value="<?= e($r['nickname']) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Real name (optional, shown publicly)')) ?></label><input class="input" name="real_name" maxlength="80" value="<?= e($r['real_name']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Role / position')) ?></label><input class="input" name="role" maxlength="60" value="<?= e($r['role']) ?>" placeholder="Captain · AWPer"></div>
    <div class="form-row"><label class="label"><?= e(t('Squad / group')) ?></label><input class="input" name="squad" maxlength="60" list="squads" value="<?= e($r['squad']) ?>"><datalist id="squads"><?php foreach ($squads as $s): ?><option value="<?= e($s) ?>"><?php endforeach ?></datalist></div>
    <div class="form-row"><label class="label"><?= e(t('Country (2-letter code)')) ?></label><input class="input" name="country" maxlength="2" pattern="[A-Za-z]{2}" value="<?= e($r['country']) ?>" placeholder="DE"></div>
    <div class="form-row"><label class="label"><?= e(t('Joined')) ?></label><input class="input" type="date" name="joined_on" value="<?= e((string) $r['joined_on']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Linked site account (username, optional)')) ?></label><input class="input" name="username" maxlength="32" value="<?= e($linked) ?>" autocomplete="off"></div>
    <div class="form-row"><label class="label"><?= e(t('Sort order')) ?></label><input class="input" type="number" name="sort_order" value="<?= (int) $r['sort_order'] ?>"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Bio')) ?></label><textarea class="textarea" name="bio" rows="4" maxlength="1500"><?= e($r['bio']) ?></textarea></div>
  <div class="form-row cover-edit"><label class="label"><?= e(t('Photo')) ?></label><?php if ($ph): ?><img class="cover-preview" src="<?= e($ph) ?>" alt="" width="120" height="150"><?php endif ?>
    <input class="input" type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"><small class="help"><?= e(t('Cropped to 4:5 with rounded corners. Without a photo the player gets a coloured initials tile.')) ?></small>
    <?php if ($ph): ?><label class="checkbox"><input type="checkbox" name="remove_photo" value="1"> <?= e(t('Remove the photo')) ?></label><?php endif ?></div>
  <fieldset class="btn-fields"><legend class="label"><?= e(t('Stats (label + value, up to 6)')) ?></legend><div class="stat-edit"><?php foreach ($stats as $s): ?><div class="inline-form"><input class="input" name="stat_label[]" maxlength="20" value="<?= e($s['label']) ?>" placeholder="K/D"><input class="input" name="stat_value[]" maxlength="20" value="<?= e($s['value']) ?>" placeholder="1.32"></div><?php endforeach ?></div></fieldset>
  <fieldset class="btn-fields"><legend class="label"><?= e(t('Social links')) ?></legend><div class="form-grid"><?php foreach (Roster::SOCIALS as $k => [$label, $icon, $kind]): ?><div class="form-row"><label class="label"><?= e($icon . ' ' . $label) ?></label><input class="input" name="social[<?= e($k) ?>]" maxlength="200" value="<?= e($r['socials_a'][$k] ?? '') ?>" placeholder="<?= $kind === 'url' ? 'https://…' : 'name#0000' ?>"></div><?php endforeach ?></div></fieldset>
  <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>> <?= e(t('Active member (uncheck for former members)')) ?></label>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Save member')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/roster')) ?>"><?= e(t('Back')) ?></a></p>
</div></form>
