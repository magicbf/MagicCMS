<?php /** @var array $atts existing attachments (edit mode) */
use MagicCMS\Modules\Forum\Attachments;
if (!Attachments::allowed()) return; ?>
<div class="form-row attach-field">
  <label class="label">📎 <?= e(t('Attachments')) ?></label>
  <?php foreach ($atts as $a): ?><label class="checkbox att-existing"><input type="checkbox" name="remove_att[]" value="<?= (int) $a['id'] ?>"> <?= e(t('Remove')) ?>: <strong><?= e($a['orig_name']) ?></strong> <span class="muted">(<?= e(Attachments::size((int) $a['size'])) ?>)</span></label><?php endforeach ?>
  <input class="input" type="file" name="files[]" multiple accept="<?= Attachments::docsAllowed() ? 'image/*,.pdf,.txt,.zip,.7z' : 'image/*' ?>">
  <small class="help"><?= e(t('Up to {n} files per post, {mb} MB each. Allowed: images (JPG, PNG, GIF, WebP – re-encoded, GIFs lose animation)', ['n' => Attachments::maxFiles(), 'mb' => Attachments::maxBytes() / 1048576])) ?><?= Attachments::docsAllowed() ? e(', PDF, TXT, ZIP, 7Z') : '' ?>.</small>
</div>
