<?php /** @var ?array $row @var array $cats @var string $tags */ use MagicCMS\Modules\News\News;
$r = $row ?? ['id' => 0, 'title' => '', 'slug' => '', 'category_id' => 0, 'excerpt' => '', 'body' => '', 'cover' => null, 'status' => 'draft', 'featured' => 0, 'comments_enabled' => 1, 'seo_title' => '', 'seo_desc' => '', 'publish_at' => null, 'cat_color' => '#ff5238', 'cat_style' => 'auto', 'cat_image' => null];
$preview = $r['id'] ? News::coverUrl($r + ['cat_name' => '']) : null; ?>
<form class="card" method="post" action="<?= e(url('admin/news/save')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
<div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Title')) ?></label><input class="input" name="title" maxlength="160" value="<?= e($r['title']) ?>" required></div>
    <div class="form-row"><label class="label">Slug</label><input class="input" name="slug" maxlength="90" value="<?= e($r['slug']) ?>" placeholder="auto"><small class="help">/news/<em>slug</em></small></div>
  </div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Category')) ?></label><select class="select" name="category_id"><option value="0">— <?= e(t('Uncategorised')) ?> —</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $r['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e(($c['icon'] ? $c['icon'] . ' ' : '') . $c['name']) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Tags (comma separated)')) ?></label><input class="input" name="tags" maxlength="300" value="<?= e($tags) ?>" placeholder="scrim, roster, cs2"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Excerpt (shown on cards and as intro)')) ?></label><textarea class="textarea" name="excerpt" rows="2" maxlength="400"><?= e($r['excerpt']) ?></textarea></div>
  <div class="form-row"><label class="label"><?= e(t('Content (HTML – unsafe tags are removed automatically)')) ?></label><textarea class="textarea mono" name="body" rows="14"><?= e($r['body']) ?></textarea></div>
  <div class="form-row cover-edit"><label class="label"><?= e(t('Cover image')) ?></label>
    <?php if ($preview): ?><img class="cover-preview" src="<?= e($preview) ?>" alt="" width="240" height="135"><?php endif ?>
    <input class="input" type="file" name="cover" accept="image/jpeg,image/png,image/gif,image/webp">
    <small class="help"><?= e(t('Optional. Cropped to 16:9. Without a picture the category picture is used, or an abstract cover is generated in the category colour.')) ?></small>
    <?php if ($r['cover']): ?><label class="checkbox"><input type="checkbox" name="remove_cover" value="1"> <?= e(t('Remove the uploaded cover')) ?></label><?php endif ?></div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Status')) ?></label><select class="select" name="status"><option value="draft" <?= $r['status'] === 'draft' ? 'selected' : '' ?>><?= e(t('Draft')) ?></option><option value="published" <?= $r['status'] === 'published' ? 'selected' : '' ?>><?= e(t('Published')) ?></option></select></div>
    <div class="form-row"><label class="label"><?= e(t('Publish date (a future date schedules the post)')) ?></label><input class="input" type="datetime-local" name="publish_at" value="<?= e(News::utcToLocal($r['publish_at'])) ?>"></div>
  </div>
  <label class="checkbox"><input type="checkbox" name="featured" value="1" <?= $r['featured'] ? 'checked' : '' ?>> <?= e(t('Featured (pinned first; can feed the hero banner)')) ?></label>
  <label class="checkbox"><input type="checkbox" name="comments_enabled" value="1" <?= $r['comments_enabled'] ? 'checked' : '' ?>> <?= e(t('Allow comments')) ?></label>
  <details class="mt"><summary class="muted"><?= e(t('SEO')) ?></summary><div class="form-grid mt">
    <div class="form-row"><label class="label"><?= e(t('SEO title')) ?></label><input class="input" name="seo_title" maxlength="160" value="<?= e($r['seo_title']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('SEO description')) ?></label><input class="input" name="seo_desc" maxlength="300" value="<?= e($r['seo_desc']) ?>"></div></div></details>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Save post')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/news')) ?>"><?= e(t('Back')) ?></a>
  <?php if ($r['id']): ?><a class="btn btn-ghost" href="<?= e(url('news/' . $r['slug'])) ?>"><?= e(t('View')) ?></a><?php endif ?></p>
</div></form>
