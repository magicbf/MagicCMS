<?php /** @var array $positions @var array $questions */ use MagicCMS\Plugins\Recruit\Recruit; ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/recruit')) ?>">← <?= e(t('Applications')) ?></a></p>
<form class="card" method="post" action="<?= e(url('admin/recruit/settings')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Settings')) ?></h2><div class="card-body">
  <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= setting('recruit.enabled', true) ? 'checked' : '' ?>> <?= e(t('Accept applications')) ?></label>
  <label class="checkbox"><input type="checkbox" name="require_login" value="1" <?= setting('recruit.require_login') ? 'checked' : '' ?>> <?= e(t('Only registered members can apply')) ?></label>
  <div class="form-row"><label class="label"><?= e(t('Intro text')) ?></label><textarea class="textarea" name="intro" rows="3" maxlength="1000"><?= e((string) setting('recruit.intro', '')) ?></textarea></div>
  <div class="form-row"><label class="label"><?= e(t('Also e-mail new applications to (comma separated, max 5)')) ?></label><input class="input" name="notify_emails" value="<?= e((string) setting('recruit.notify_emails', '')) ?>"></div>
  <div class="form-row"><label class="label"><?= e(t('Delete decided applications after … days (0 = keep forever)')) ?></label><input class="input" type="number" min="0" max="3650" name="retention_days" value="<?= (int) setting('recruit.retention_days', 180) ?>"></div>
  <p><button class="btn btn-primary"><?= e(t('Save settings')) ?></button></p></div></form>
<div class="card" id="positions"><h2 class="card-title"><?= e(t('Positions')) ?></h2><div class="card-body">
<?php foreach (array_merge($positions, [['id' => 0, 'title' => '', 'description' => '', 'is_open' => 1, 'sort_order' => 0]]) as $p): ?>
  <form class="rc-row" method="post" action="<?= e(url('admin/recruit/position')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    <input class="input" name="title" maxlength="80" value="<?= e($p['title']) ?>" placeholder="<?= e($p['id'] ? '' : t('New position')) ?>" required><input class="input" name="description" maxlength="300" value="<?= e($p['description']) ?>" placeholder="<?= e(t('Short description')) ?>">
    <input class="input num" type="number" name="sort_order" value="<?= (int) $p['sort_order'] ?>" title="<?= e(t('Order')) ?>"><label class="checkbox"><input type="checkbox" name="is_open" value="1" <?= $p['is_open'] ? 'checked' : '' ?>> <?= e(t('Open')) ?></label>
    <button class="btn btn-sm btn-primary"><?= e($p['id'] ? t('Save') : t('Add')) ?></button>
    <?php if ($p['id']): ?><button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/recruit/position/delete/' . (int) $p['id'])) ?>" formnovalidate data-confirm="<?= e(t('Delete?')) ?>">✕</button><?php endif ?></form>
<?php endforeach ?></div></div>
<div class="card" id="questions"><h2 class="card-title"><?= e(t('Questions')) ?></h2><div class="card-body">
<?php foreach (array_merge($questions, [['id' => 0, 'label' => '', 'help' => '', 'qtype' => 'text', 'options' => '', 'required' => 1, 'enabled' => 1, 'sort_order' => 0]]) as $q): ?>
  <form class="rc-row rc-q" method="post" action="<?= e(url('admin/recruit/question')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
    <input class="input" name="label" maxlength="200" value="<?= e($q['label']) ?>" placeholder="<?= e($q['id'] ? '' : t('New question')) ?>" required>
    <select class="select" name="qtype"><?php foreach (Recruit::TYPES as $k => $l): ?><option value="<?= e($k) ?>" <?= $q['qtype'] === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select>
    <input class="input" name="options" maxlength="500" value="<?= e($q['options']) ?>" placeholder="<?= e(t('Choices, separated by |')) ?>"><input class="input" name="help" maxlength="200" value="<?= e($q['help']) ?>" placeholder="<?= e(t('Hint (optional)')) ?>">
    <input class="input num" type="number" name="sort_order" value="<?= (int) $q['sort_order'] ?>" title="<?= e(t('Order')) ?>">
    <label class="checkbox"><input type="checkbox" name="required" value="1" <?= $q['required'] ? 'checked' : '' ?>> <?= e(t('Required')) ?></label><label class="checkbox"><input type="checkbox" name="enabled" value="1" <?= $q['enabled'] ? 'checked' : '' ?>> <?= e(t('Shown')) ?></label>
    <button class="btn btn-sm btn-primary"><?= e($q['id'] ? t('Save') : t('Add')) ?></button>
    <?php if ($q['id']): ?><button class="btn btn-sm btn-danger" formaction="<?= e(url('admin/recruit/question/delete/' . (int) $q['id'])) ?>" formnovalidate data-confirm="<?= e(t('Delete?')) ?>">✕</button><?php endif ?></form>
<?php endforeach ?></div></div>
