<?php /** @var string $body @var int $rows */ ?>
<div class="editor" data-editor>
  <div class="editor-toolbar" role="toolbar" aria-label="BBCode">
    <button type="button" class="btn btn-sm btn-ghost" data-bb="b" title="Bold"><b>B</b></button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="i" title="Italic"><i>I</i></button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="u" title="Underline"><u>U</u></button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="s" title="Strikethrough"><s>S</s></button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="quote" title="Quote">❝</button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="code" title="Code">&lt;/&gt;</button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="url" title="Link">🔗</button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="img" title="Image (https)">🖼</button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="list" title="List">•••</button>
    <button type="button" class="btn btn-sm btn-ghost" data-bb="spoiler" title="Spoiler">👁</button>
    <button type="button" class="btn btn-sm btn-ghost" data-emoji-btn title="<?= e(t('Smileys & flags')) ?>">😀</button>
    <button type="button" class="btn btn-sm btn-ghost editor-preview-btn" data-preview-url="<?= e(url('forum/preview')) ?>"><?= e(t('Preview')) ?></button>
  </div>
  <textarea class="textarea" name="body" rows="<?= (int) ($rows ?? 10) ?>" maxlength="20000" required aria-label="<?= e(t('Message')) ?>"><?= e($body) ?></textarea>
  <div class="editor-preview post-body card-body" hidden></div>
</div>
<script src="<?= e(asset('forum.js')) ?>" defer></script>
