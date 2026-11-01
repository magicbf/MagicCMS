<?php /** @var array $row @var array $schema @var array $values */ ?>
<form class="card" method="post" action="<?= e(url('admin/panels/' . (int) $row['id'])) ?>"><?= csrf_field() ?>
<h2 class="card-title"><?= e($row['title']) ?> <small>(<?= e($row['panel_key']) ?>)</small></h2><div class="card-body">
<?php foreach ($schema as $name => $def): $val = $values[$name] ?? ''; $type = $def['type'] ?? 'text'; ?>
  <div class="form-row"><label class="label" for="s_<?= e($name) ?>"><?= e($def['label'] ?? $name) ?></label>
  <?php if ($type === 'textarea'): ?><textarea class="textarea" rows="8" id="s_<?= e($name) ?>" name="s[<?= e($name) ?>]"><?= e($val) ?></textarea>
  <?php elseif ($type === 'checkbox'): ?><input type="checkbox" id="s_<?= e($name) ?>" name="s[<?= e($name) ?>]" value="1" <?= $val ? 'checked' : '' ?>>
  <?php else: ?><input class="input" id="s_<?= e($name) ?>" type="<?= $type === 'number' ? 'number' : 'text' ?>" name="s[<?= e($name) ?>]" value="<?= e($val) ?>"><?php endif ?></div>
<?php endforeach ?>
<button class="btn btn-primary"><?= e(t('Save')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/panels')) ?>"><?= e(t('Back')) ?></a>
</div></form>
