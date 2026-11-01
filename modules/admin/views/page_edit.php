<?php /** @var ?array $row */ $r = $row ?? ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'layout' => '', 'published' => 1]; ?>
<form class="card" method="post" action="<?= e(url('admin/pages/save')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
<div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Title')) ?></label><input class="input" name="title" maxlength="160" value="<?= e($r['title']) ?>" required></div>
    <div class="form-row"><label class="label">Slug</label><input class="input" name="slug" maxlength="100" value="<?= e($r['slug']) ?>" placeholder="auto"><small class="help">/p/<em>slug</em></small></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Content (HTML – unsafe tags are removed automatically)')) ?></label><textarea class="textarea mono" name="content" rows="14"><?= e($r['content']) ?></textarea></div>
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Side panels')) ?></label><select class="select" name="layout"><?php foreach (['' => 'Use site default', 'none' => 'None (full width)', 'left' => 'Left only', 'right' => 'Right only', 'both' => 'Left + right'] as $k => $l): ?><option value="<?= $k ?>" <?= $r['layout'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
    <label class="checkbox mt"><input type="checkbox" name="published" value="1" <?= $r['published'] ? 'checked' : '' ?>> <?= e(t('Published')) ?></label>
  </div>
  <button class="btn btn-primary"><?= e(t('Save page')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/pages')) ?>"><?= e(t('Back')) ?></a>
  <?php if ($r['id']): ?><a class="btn btn-ghost" href="<?= e(url('p/' . $r['slug'])) ?>"><?= e(t('View')) ?></a><?php endif ?>
</div></form>
