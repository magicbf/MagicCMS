<?php /** @var ?array $row */ $r = $row ?? ['id' => 0, 'name' => '', 'url' => '', 'logo' => '', 'enabled' => 1, 'sort_order' => 0]; $img = upload_url('partners', $r['logo']); ?>
<form class="card" method="post" action="<?= e(url('admin/partners/save')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><h2 class="card-title"><?= e($r['id'] ? t('Edit partner') : t('New partner')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Name')) ?></label><input class="input" name="name" maxlength="80" value="<?= e($r['name']) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Website (optional)')) ?></label><input class="input" name="url" maxlength="255" value="<?= e($r['url']) ?>" placeholder="https://…"></div>
  </div>
  <div class="form-row cover-edit"><label class="label"><?= e(t('Logo')) ?></label><?php if ($img): ?><img class="cover-preview" src="<?= e($img) ?>" alt="" style="max-height:80px;width:auto;background:rgba(128,128,128,.25);padding:8px;border-radius:8px"><?php endif ?>
    <input class="input" type="file" name="logo" accept="image/png,image/webp,image/jpeg,image/gif" <?= $img ? '' : 'required' ?>><small class="help"><?= e(t('Transparent PNG/WebP works best (wide logos ≈ 300×120). The image is re-encoded and resized; max 5 MB.')) ?></small></div>
  <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= $r['enabled'] ? 'checked' : '' ?>> <?= e(t('Show in the strip')) ?></label>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Save')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/partners')) ?>"><?= e(t('Back')) ?></a></p>
</div></form>
