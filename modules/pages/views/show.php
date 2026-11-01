<?php /** @var array $page */ ?>
<article class="card"><h1 class="card-title"><?= e($page['title']) ?></h1>
<div class="card-body prose"><?= MagicCMS\Core\Emoji::render($page['content']) /* sanitized on save */ ?></div></article>
<?php if (can('admin.pages')): ?><p><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/pages/' . (int) $page['id'])) ?>">✎ <?= e(t('Edit page')) ?></a><?= $page['published'] ? '' : ' <span class="badge">' . e(t('Draft')) . '</span>' ?></p><?php endif ?>
