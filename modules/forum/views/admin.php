<?php /** @var array $cats @var array $boards @var int $reports */ use MagicCMS\Modules\Forum\Forum; ?>
<p class="muted"><?= e(t('Read / post level = the lowest role that may see / post in a board. Moderators can always manage topics in boards they can read.')) ?> <?php if ($reports): ?><a href="<?= e(url('forum/reports')) ?>"><strong><?= $reports ?> <?= e(t('open report(s)')) ?></strong></a><?php endif ?></p>
<?php $tops = array_values(array_filter($boards, fn($b) => !$b['parent_id']));
$levelSel = function (string $name, int $cur) { $o = '<select class="select" name="' . $name . '">'; foreach (Forum::LEVELS as $k => $l) { $o .= '<option value="' . $k . '"' . ($k === $cur ? ' selected' : '') . '>' . e(t($l)) . '</option>'; } return $o . '</select>'; };
$catSel = function (int $cur) use ($cats) { $o = '<select class="select" name="category_id" aria-label="Category">'; foreach ($cats as $c) { $o .= '<option value="' . (int) $c['id'] . '"' . ((int) $c['id'] === $cur ? ' selected' : '') . '>' . e($c['title']) . '</option>'; } return $o . '</select>'; };
$parSel = function (int $cur, int $self) use ($tops) { $o = '<select class="select" name="parent_id" aria-label="Parent"><option value="0">— ' . e(t('top level')) . ' —</option>'; foreach ($tops as $b) { if ((int) $b['id'] === $self) continue; $o .= '<option value="' . (int) $b['id'] . '"' . ((int) $b['id'] === $cur ? ' selected' : '') . '>' . e($b['title']) . '</option>'; } return $o . '</select>'; };
$boardForm = function (array $b) use ($levelSel, $catSel, $parSel) { ob_start(); ?>
  <form class="panel-row board-form" method="post" action="<?= e(url('admin/forum/board')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
    <input class="input" name="title" value="<?= e($b['title']) ?>" placeholder="<?= e(t('Board title')) ?>" aria-label="Title" maxlength="120" required>
    <input class="input" name="description" value="<?= e($b['description']) ?>" placeholder="<?= e(t('Description')) ?>" aria-label="Description" maxlength="300">
    <?= $catSel((int) $b['category_id']) ?><?= $parSel((int) $b['parent_id'], (int) $b['id']) ?>
    <label class="small"><?= e(t('Read')) ?> <?= $levelSel('read_level', (int) $b['read_level']) ?></label>
    <label class="small"><?= e(t('Post')) ?> <?= $levelSel('post_level', (int) $b['post_level']) ?></label>
    <input class="input narrow" type="number" name="sort_order" value="<?= (int) $b['sort_order'] ?>" aria-label="Order">
    <label class="checkbox"><input type="checkbox" name="is_locked" value="1" <?= $b['is_locked'] ? 'checked' : '' ?>> <?= e(t('Locked')) ?></label>
    <button class="btn btn-sm btn-primary"><?= e($b['id'] ? t('Save') : t('Add board')) ?></button>
    <?php if ($b['id']): ?><button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/forum/board/delete/' . (int) $b['id'])) ?>" data-confirm="<?= e(t('Delete this board and ALL its topics?')) ?>">✕</button><?php endif ?>
  </form><?php return ob_get_clean(); }; ?>
<?php foreach ($cats as $c): ?>
<div class="card"><h2 class="card-title"><?= e($c['title']) ?></h2><div class="card-body">
  <form class="inline-form" method="post" action="<?= e(url('admin/forum/category')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
    <input class="input" name="title" value="<?= e($c['title']) ?>" maxlength="120" aria-label="Category title"><input class="input narrow" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" aria-label="Order"><button class="btn btn-sm btn-primary"><?= e(t('Rename / order')) ?></button>
    <button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/forum/category/delete/' . (int) $c['id'])) ?>" data-confirm="<?= e(t('Delete this category?')) ?>">✕</button></form>
  <?php foreach ($boards as $b): if ((int) $b['category_id'] !== (int) $c['id']) continue; echo $boardForm($b); endforeach ?>
</div></div>
<?php endforeach ?>
<div class="card"><h2 class="card-title"><?= e(t('Add category')) ?></h2><form class="card-body inline-form" method="post" action="<?= e(url('admin/forum/category')) ?>"><?= csrf_field() ?>
  <input class="input" name="title" placeholder="<?= e(t('Category title')) ?>" maxlength="120" required><input class="input narrow" type="number" name="sort_order" value="0" aria-label="Order"><button class="btn btn-primary"><?= e(t('Add')) ?></button></form></div>
<?php if ($cats): ?><div class="card"><h2 class="card-title"><?= e(t('Add board')) ?></h2><div class="card-body"><?= $boardForm(['id' => 0, 'title' => '', 'description' => '', 'category_id' => $cats[0]['id'], 'parent_id' => 0, 'read_level' => 0, 'post_level' => 10, 'sort_order' => 0, 'is_locked' => 0]) ?></div></div><?php endif ?>
