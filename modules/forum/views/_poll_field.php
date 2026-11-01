<?php /** @var ?array $poll @var bool $pollLocked */
use MagicCMS\Modules\Forum\Polls;
if ($pollLocked): ?><p class="muted">📊 <?= e(t('This topic has a poll with votes – it can no longer be edited. Moderators and the author can end or remove it from the topic page.')) ?></p><?php return; endif;
$on = $poll !== null || input('poll_enable') === '1';
$opts = $poll ? implode("\n", array_column($poll['options'], 'label')) : (string) ($_POST['poll_options'] ?? '');
$q = $poll['question'] ?? (string) ($_POST['poll_question'] ?? '');
$multi = $poll ? (bool) $poll['multiple'] : input('poll_multiple') === '1';
$max = $poll ? (int) $poll['max_choices'] : (int) input('poll_max', 2);
$res = $poll['results'] ?? (string) input('poll_results', 'after_vote'); ?>
<details class="poll-field card-inset" <?= $on ? 'open' : '' ?>><summary>📊 <?= e(t('Poll')) ?></summary>
  <label class="checkbox"><input type="checkbox" name="poll_enable" value="1" <?= $on ? 'checked' : '' ?>> <?= e($poll ? t('This topic has a poll (uncheck to remove it)') : t('Add a poll to this topic')) ?></label>
  <div class="form-row"><label class="label"><?= e(t('Question')) ?></label><input class="input" name="poll_question" maxlength="200" value="<?= e($q) ?>"></div>
  <div class="form-row"><label class="label"><?= e(t('Options (one per line, 2–10)')) ?></label><textarea class="textarea" name="poll_options" rows="5" maxlength="1200"><?= e($opts) ?></textarea></div>
  <div class="form-grid">
    <div class="form-row"><label class="checkbox"><input type="checkbox" name="poll_multiple" value="1" <?= $multi ? 'checked' : '' ?>> <?= e(t('Allow multiple choices')) ?></label><label class="label"><?= e(t('Maximum choices')) ?></label><input class="input" type="number" name="poll_max" min="2" max="10" value="<?= $max ?: 2 ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Ends')) ?></label><select class="select" name="poll_days"><?php foreach (Polls::DURATIONS as $d => $l): ?><option value="<?= $d ?>"><?= e(t($l)) ?></option><?php endforeach ?></select>
      <label class="label"><?= e(t('Results are shown')) ?></label><select class="select" name="poll_results"><?php foreach (Polls::RESULTS as $k => $l): ?><option value="<?= e($k) ?>" <?= $res === $k ? 'selected' : '' ?>><?= e(t($l)) ?></option><?php endforeach ?></select></div>
  </div>
  <small class="help"><?= e(t('Votes are anonymous. Members can change their vote until the poll ends.')) ?></small>
</details>
