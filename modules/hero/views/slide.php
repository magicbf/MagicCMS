<?php /** @var ?array $row @var array $targets @var array $pages */
$r = $row ?? ['id' => 0, 'enabled' => 1, 'eyebrow' => '', 'headline' => '', 'subline' => '', 'image' => null, 'overlay' => 45, 'align' => 'left', 'color_from' => '', 'color_to' => '', 'starts_at' => null, 'ends_at' => null, 'media_type' => 'none', 'media_side' => 'right', 'media_image' => null, 'media_youtube' => ''];
$mimg = upload_url('hero', $r['media_image']);
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
  <fieldset class="btn-fields media-fields"><legend class="label"><?= e(t('Featured media (rounded, next to the text)')) ?></legend>
    <div class="form-grid">
      <div class="form-row"><label class="label"><?= e(t('Show')) ?></label><select class="select" name="media_type" data-ref-switch>
        <?php foreach (['none' => 'Nothing', 'image' => 'A rectangular image', 'youtube' => 'A YouTube video'] as $k => $l): ?><option value="<?= $k ?>" <?= $r['media_type'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
      <div class="form-row"><label class="label"><?= e(t('Side')) ?></label><select class="select" name="media_side"><option value="right" <?= $r['media_side'] === 'right' ? 'selected' : '' ?>><?= e(t('Right of the text')) ?></option><option value="left" <?= $r['media_side'] === 'left' ? 'selected' : '' ?>><?= e(t('Left of the text')) ?></option></select></div>
    </div>
    <div class="form-row" data-ref="youtube"><label class="label"><?= e(t('YouTube link')) ?></label><input class="input" name="media_youtube" maxlength="200" value="<?= e($r['media_youtube'] ? 'https://youtu.be/' . $r['media_youtube'] : '') ?>" placeholder="https://www.youtube.com/watch?v=…">
      <small class="help"><?= e(t('Plays in the banner after a click, via youtube-nocookie.com. Nothing is requested from YouTube until a visitor presses play – unless you leave the poster empty, in which case YouTube\'s thumbnail is shown.')) ?></small></div>
    <div class="form-row cover-edit" data-ref="image youtube"><label class="label"><span data-label-image><?= e(t('Image')) ?></span> / <?= e(t('video poster (optional)')) ?></label>
      <?php if ($mimg): ?><img class="cover-preview" src="<?= e($mimg) ?>" alt="" width="240" height="135"><?php endif ?>
      <input class="input" type="file" name="media_image" accept="image/jpeg,image/png,image/gif,image/webp">
      <small class="help"><?= e(t('Cropped to 16:9 with rounded corners. For a video this is the poster; leave empty to use the YouTube thumbnail.')) ?></small>
      <?php if ($mimg): ?><label class="checkbox"><input type="checkbox" name="remove_media_image" value="1"> <?= e(t('Remove this picture')) ?></label><?php endif ?></div>
  </fieldset>
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
