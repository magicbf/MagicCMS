<?php /** @var ?array $row @var array $targets @var array $pages */
$r = $row ?? ['id' => 0, 'enabled' => 1, 'eyebrow' => '', 'headline' => '', 'subline' => '', 'image' => null, 'overlay' => 45, 'align' => 'left', 'color_from' => '', 'color_to' => '', 'starts_at' => null, 'ends_at' => null];
$img = upload_url('hero', $r['image']); ?>
<form class="card" method="post" action="<?= e(url('admin/hero/slide/save')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
<h2 class="card-title"><?= e($r['id'] ? t('Edit slide') : t('New slide')) ?></h2><div class="card-body">
  <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= $r['enabled'] ? 'checked' : '' ?>> <?= e(t('Slide is active')) ?></label>
  <div class="form-grid mt">
    <div class="form-row"><label class="label"><?= e(t('Small label above the headline')) ?></label><input class="input" name="eyebrow" maxlength="80" value="<?= e($r['eyebrow']) ?>" placeholder="SEASON 7"></div>
    <div class="form-row"><label class="label"><?= e(t('Text alignment')) ?></label><select class="select" name="align"><?php foreach (['left' => 'Left', 'center' => 'Centre', 'right' => 'Right'] as $k => $l): ?><option value="<?= $k ?>" <?= $r['align'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Headline')) ?></label><input class="input" name="headline" maxlength="140" value="<?= e($r['headline']) ?>"></div>
  <div class="form-row"><label class="label"><?= e(t('Sub-headline')) ?></label><textarea class="textarea" name="subline" rows="2" maxlength="300"><?= e($r['subline']) ?></textarea></div>
  <div class="form-row cover-edit"><label class="label"><?= e(t('Background picture')) ?></label>
    <?php if ($img): ?><img class="cover-preview" src="<?= e($img) ?>" alt="" width="320" height="130"><?php endif ?>
    <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
    <small class="help"><?= e(t('Optional (up to 5 MB, resized to 1920 px wide). Without a picture the slide uses the animated colour gradient.')) ?></small>
    <?php if ($img): ?><label class="checkbox"><input type="checkbox" name="remove_image" value="1"> <?= e(t('Remove the picture')) ?></label><?php endif ?></div>
  <div class="form-row"><label class="label"><?= e(t('Darkening overlay on pictures')) ?>: <output id="ovv"><?= (int) $r['overlay'] ?></output>%</label><input type="range" name="overlay" min="0" max="90" value="<?= (int) $r['overlay'] ?>" data-out="ovv"></div>
  <?= render('hero/_btn', ['n' => '1', 'r' => $r, 'targets' => $targets, 'pages' => $pages]) ?>
  <?= render('hero/_btn', ['n' => '2', 'r' => $r, 'targets' => $targets, 'pages' => $pages]) ?>
  <fieldset class="btn-fields"><legend class="label"><?= e(t('Gradient colours (no picture)')) ?></legend><div class="inline-form">
    <label class="checkbox"><input type="checkbox" name="color_from_on" value="1" <?= $r['color_from'] ? 'checked' : '' ?>> <?= e(t('Custom from')) ?></label><input type="color" name="color_from" value="<?= e($r['color_from'] ?: '#ff5238') ?>">
    <label class="checkbox"><input type="checkbox" name="color_to_on" value="1" <?= $r['color_to'] ? 'checked' : '' ?>> <?= e(t('Custom to')) ?></label><input type="color" name="color_to" value="<?= e($r['color_to'] ?: '#e5091f') ?>">
    <small class="help"><?= e(t('Unchecked = the site colour scheme.')) ?></small></div></fieldset>
  <div class="form-grid"><div class="form-row"><label class="label"><?= e(t('Show from (optional)')) ?></label><input class="input" type="datetime-local" name="starts_at" value="<?= e(utc_to_local($r['starts_at'])) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Show until (optional)')) ?></label><input class="input" type="datetime-local" name="ends_at" value="<?= e(utc_to_local($r['ends_at'])) ?>"></div></div>
  <button class="btn btn-primary"><?= e(t('Save slide')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/hero')) ?>"><?= e(t('Back')) ?></a>
</div></form>
