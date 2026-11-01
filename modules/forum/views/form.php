<?php /** @var string $mode @var ?array $b @var ?array $t @var string $title @var string $body @var string $action @var bool $canType @var array $recent */
use MagicCMS\Core\Bbcode;
$heading = ['new' => t('New topic'), 'reply' => t('Reply'), 'edit' => t('Edit post'), 'edit_first' => t('Edit topic')][$mode]; ?>
<?php $ctx = $b ?: $t; ?>
<?= render('forum/_crumbs', ['items' => array_filter([[$ctx['category_title'], url('forum/category/' . (int) $ctx['category_id'])], [$b ? $b['title'] : $t['board_title'], url('forum/subcategory/' . (int) ($b ? $b['id'] : $t['board_id']))], $t ? [$t['title'], url('forum/topic/' . (int) $t['id'])] : null, [$heading, null]])]) ?>
<form class="card" method="post" action="<?= e($action) ?>"><?= csrf_field() ?><h1 class="card-title"><?= e($heading) ?></h1>
<div class="card-body">
  <?php if ($mode === 'new' || $mode === 'edit_first'): ?><div class="form-row"><label class="label" for="ft"><?= e(t('Title')) ?></label><input class="input" id="ft" name="title" value="<?= e($title) ?>" maxlength="120" required autofocus></div><?php endif ?>
  <?php if ($canType): ?><div class="form-row"><label class="label" for="ty"><?= e(t('Topic type')) ?></label><select class="select" id="ty" name="type"><option value="normal"><?= e(t('Normal')) ?></option><option value="sticky"><?= e(t('Sticky')) ?></option><option value="announce"><?= e(t('Announcement')) ?></option></select></div><?php endif ?>
  <?= render('forum/_editor', ['body' => $body, 'rows' => 12]) ?>
  <p class="muted small"><?= e(t('BBCode: [b] [i] [u] [s] [quote] [code] [url] [img] (https) [list] [spoiler]. HTML is not allowed.')) ?></p>
  <button class="btn btn-primary"><?= e($mode === 'reply' ? t('Post reply') : t('Save')) ?></button>
  <a class="btn btn-ghost" href="<?= e($t ? url('forum/topic/' . (int) $t['id']) : ($b ? url('forum/subcategory/' . (int) $b['id']) : url('forum'))) ?>"><?= e(t('Cancel')) ?></a>
</div></form>
<?php if (!empty($recent)): ?><div class="card"><h2 class="card-title"><?= e(t('Latest posts in this topic')) ?></h2><div class="card-body"><?php foreach ($recent as $r): ?><div class="recent-post"><strong><?= e($r['username']) ?></strong><div class="post-body"><?= Bbcode::render($r['body']) ?></div></div><?php endforeach ?></div></div><?php endif ?>
