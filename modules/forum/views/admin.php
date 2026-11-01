<?php /** @var array $cats @var array $boards @var int $reports @var string $layout */ use MagicCMS\Modules\Forum\Forum; ?>
<div class="card"><h2 class="card-title"><?= e(t('Forum layout')) ?></h2>
<form class="card-body" method="post" action="<?= e(url('admin/forum/layout')) ?>"><?= csrf_field() ?>
  <p class="muted"><?= e(t('The forum is shown inside the normal site (header, menu, footer) and takes over the main content area. By default it uses the full width with no side panels. Choose which side panels should stay visible on forum pages:')) ?></p>
  <div class="swatches" role="radiogroup" aria-label="<?= e(t('Side panels on forum pages')) ?>">
  <?php foreach (Forum::LAYOUTS as $k => $label): ?>
    <label class="swatch layout-opt"><input type="radio" name="forum_layout" value="<?= e($k) ?>" <?= $layout === $k ? 'checked' : '' ?>>
      <span class="swatch-chip layout-chip lc-<?= e($k) ?>"><i></i><i></i><i></i></span><span><?= e(t($label)) ?></span></label>
  <?php endforeach ?>
  </div>
  <button class="btn btn-primary"><?= e(t('Save layout')) ?></button>
</form></div>
<p class="muted"><?= e(t('Structure: Category → Subcategory → Topics. A subcategory\'s read / post level = the lowest role that may see / post in it. Moderators can always manage topics in subcategories they can read.')) ?> <?php if ($reports): ?><a href="<?= e(url('forum/reports')) ?>"><strong><?= $reports ?> <?= e(t('open report(s)')) ?></strong></a><?php endif ?></p>
<?php
$levelSel = function (string $name, int $cur) { $o = '<select class="select" name="' . $name . '">'; foreach (Forum::LEVELS as $k => $l) { $o .= '<option value="' . $k . '"' . ($k === $cur ? ' selected' : '') . '>' . e(t($l)) . '</option>'; } return $o . '</select>'; };
$catSel = function (int $cur) use ($cats) { $o = '<select class="select" name="category_id" aria-label="Category">'; foreach ($cats as $c) { $o .= '<option value="' . (int) $c['id'] . '"' . ((int) $c['id'] === $cur ? ' selected' : '') . '>' . e($c['title']) . '</option>'; } return $o . '</select>'; };
$subForm = function (array $b) use ($levelSel, $catSel) { ob_start(); ?>
  <form class="panel-row board-form" method="post" action="<?= e(url('admin/forum/subcategory')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
    <input class="input" name="title" value="<?= e($b['title']) ?>" placeholder="<?= e(t('Subcategory title')) ?>" aria-label="Title" maxlength="120" required>
    <input class="input" name="description" value="<?= e($b['description']) ?>" placeholder="<?= e(t('Description')) ?>" aria-label="Description" maxlength="300">
    <?= $catSel((int) $b['category_id']) ?>
    <label class="small"><?= e(t('Read')) ?> <?= $levelSel('read_level', (int) $b['read_level']) ?></label>
    <label class="small"><?= e(t('Post')) ?> <?= $levelSel('post_level', (int) $b['post_level']) ?></label>
    <input class="input narrow" type="number" name="sort_order" value="<?= (int) $b['sort_order'] ?>" aria-label="Order">
    <label class="checkbox"><input type="checkbox" name="is_locked" value="1" <?= $b['is_locked'] ? 'checked' : '' ?>> <?= e(t('Locked')) ?></label>
    <button class="btn btn-sm btn-primary"><?= e($b['id'] ? t('Save') : t('Add subcategory')) ?></button>
    <?php if ($b['id']): ?><button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/forum/subcategory/delete/' . (int) $b['id'])) ?>" data-confirm="<?= e(t('Delete this subcategory and ALL its topics?')) ?>">✕</button><?php endif ?>
  </form><?php return ob_get_clean(); }; ?>
<?php foreach ($cats as $c): ?>
<div class="card"><h2 class="card-title"><?= e($c['title']) ?></h2><div class="card-body">
  <form class="inline-form" method="post" action="<?= e(url('admin/forum/category')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <input class="input" name="title" value="<?= e($c['title']) ?>" maxlength="120" aria-label="Category title"><input class="input narrow" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" aria-label="Order"><button class="btn btn-sm btn-primary"><?= e(t('Rename / order')) ?></button>
    <button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/forum/category/delete/' . (int) $c['id'])) ?>" data-confirm="<?= e(t('Delete this category?')) ?>">✕</button></form>
  <?php foreach ($boards as $b): if ((int) $b['category_id'] !== (int) $c['id']) continue; echo $subForm($b); endforeach ?>
</div></div>
<?php endforeach ?>
<div class="card"><h2 class="card-title"><?= e(t('Add category')) ?></h2><form class="card-body inline-form" method="post" action="<?= e(url('admin/forum/category')) ?>"><?= csrf_field() ?>
  <input class="input" name="title" placeholder="<?= e(t('Category title')) ?>" maxlength="120" required><input class="input narrow" type="number" name="sort_order" value="0" aria-label="Order"><button class="btn btn-primary"><?= e(t('Add')) ?></button></form></div>
<?php if ($cats): ?><div class="card"><h2 class="card-title"><?= e(t('Add subcategory')) ?></h2><div class="card-body"><?= $subForm(['id' => 0, 'title' => '', 'description' => '', 'category_id' => $cats[0]['id'], 'read_level' => 0, 'post_level' => 10, 'sort_order' => 0, 'is_locked' => 0]) ?></div></div><?php endif ?>
