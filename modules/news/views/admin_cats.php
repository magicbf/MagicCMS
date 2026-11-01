<?php /** @var array $cats @var string $style */ use MagicCMS\Core\Cover; use MagicCMS\Modules\News\News;
$styleSel = function (string $name, string $cur, bool $auto = true) { $o = '<select class="select" name="' . $name . '">'; foreach (($auto ? ['auto' => 'Auto (varies per post)'] : []) + ['blades' => 'Blades', 'orbits' => 'Orbits', 'mesh' => 'Mesh', 'waves' => 'Waves'] as $k => $l) { $o .= '<option value="' . $k . '"' . ($k === $cur ? ' selected' : '') . '>' . e(t($l)) . '</option>'; } return $o . '</select>'; };
$form = function (array $c) use ($styleSel) { ob_start(); ?>
<form class="cat-form" method="post" action="<?= e(url('admin/news/categories/save')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
  <div class="cat-form-top">
    <input class="input" type="color" name="color" value="<?= e($c['color']) ?>" aria-label="<?= e(t('Colour')) ?>">
    <input class="input narrow" name="icon" value="<?= e($c['icon']) ?>" maxlength="8" placeholder="🏆" aria-label="Icon">
    <input class="input" name="name" value="<?= e($c['name']) ?>" maxlength="80" placeholder="<?= e(t('Category name')) ?>" required aria-label="<?= e(t('Name')) ?>">
    <input class="input" name="slug" value="<?= e($c['slug']) ?>" maxlength="80" placeholder="slug (auto)" aria-label="Slug">
    <input class="input narrow" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" aria-label="Order">
  </div>
  <div class="cat-form-mid">
    <input class="input" name="description" value="<?= e($c['description']) ?>" maxlength="300" placeholder="<?= e(t('Description (optional)')) ?>" aria-label="Description">
    <?= $styleSel('cover_style', $c['cover_style']) ?>
    <input class="input" type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="<?= e(t('Category picture')) ?>">
    <?php if ($c['image']): ?><label class="checkbox"><input type="checkbox" name="remove_image" value="1"> <?= e(t('Remove picture')) ?></label><?php endif ?>
    <button class="btn btn-sm btn-primary"><?= e($c['id'] ? t('Save') : t('Add category')) ?></button>
    <?php if ($c['id']): ?><button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/news/categories/delete/' . (int) $c['id'])) ?>" data-confirm="<?= e(t('Delete this category? Its posts become uncategorised.')) ?>">✕</button><?php endif ?>
  </div>
</form><?php return ob_get_clean(); }; ?>
<div class="tabs sub"><a class="tab" href="<?= e(url('admin/news')) ?>"><?= e(t('Posts')) ?></a><a class="tab is-active" href="<?= e(url('admin/news/categories')) ?>"><?= e(t('Categories')) ?></a></div>
<div class="card"><h2 class="card-title"><?= e(t('Default cover style')) ?></h2><form class="card-body inline-form" method="post" action="<?= e(url('admin/news/categories/style')) ?>"><?= csrf_field() ?>
  <?= $styleSel('cover_style', $style) ?><button class="btn btn-sm btn-primary"><?= e(t('Save')) ?></button>
  <small class="help"><?= e(t('Posts without a picture get an abstract cover in their category colour. A category can override the style below.')) ?></small></form></div>
<div class="card"><h2 class="card-title"><?= e(t('Categories')) ?></h2><div class="card-body">
<?php foreach ($cats as $c): $thumb = upload_url('categories', $c['image']) ?: url('cover/cat/' . (int) $c['id'] . '.svg') . '?v=' . substr(md5($c['color'] . $c['cover_style']), 0, 6); ?>
  <div class="cat-row" style="--cat-color:<?= e($c['color']) ?>"><img class="cat-thumb" src="<?= e($thumb) ?>" alt="" width="128" height="72" loading="lazy"><div><?= $form($c) ?><small class="muted"><?= (int) $c['posts'] ?> <?= e(t('published posts')) ?></small></div></div>
<?php endforeach; if (!$cats): ?><p class="muted"><?= e(t('No categories yet.')) ?></p><?php endif ?></div></div>
<div class="card"><h2 class="card-title"><?= e(t('Add category')) ?></h2><div class="card-body"><?= $form(['id' => 0, 'name' => '', 'slug' => '', 'description' => '', 'color' => '#ff5238', 'icon' => '', 'image' => null, 'cover_style' => 'auto', 'sort_order' => 0]) ?></div></div>
