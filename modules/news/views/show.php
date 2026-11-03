<?php /** @var array $p @var array $tags @var array $comments @var array $related @var string $cover @var bool $preview */
use MagicCMS\Modules\News\News; $color = News::catColor($p); ?>
<?= render('news/_crumbs', ['items' => array_filter([$p['cat_name'] ? [$p['cat_name'], url('news/category/' . $p['cat_slug'])] : null, [$p['title'], null]])]) ?>
<article class="card article" style="--cat-color:<?= e($color) ?>">
  <div class="article-cover"><img src="<?= e($cover) ?>" alt="" width="1280" height="720"><div class="article-cover-fade"></div></div>
  <div class="article-head">
    <div class="news-meta"><?php if ($p['cat_name']): ?><a class="cat-badge" href="<?= e(url('news/category/' . $p['cat_slug'])) ?>"><?= $p['cat_icon'] !== '' ? e($p['cat_icon']) . ' ' : '' ?><?= e($p['cat_name']) ?></a><?php endif ?><?php if ($preview): ?><span class="badge"><?= e(t('Draft preview')) ?></span><?php endif ?><span><?= e(t('by')) ?> <a href="<?= e(url('user/' . $p['author'])) ?>"><?= e($p['author']) ?></a> · <?= e(fmt_date($p['publish_at'], 'Y-m-d H:i')) ?> · 👁 <?= (int) $p['views'] ?></span></div>
    <h1 class="article-title"><?= e($p['title']) ?></h1>
    <?php if ($p['excerpt'] !== ''): ?><p class="article-lead"><?= e($p['excerpt']) ?></p><?php endif ?>
  </div>
  <div class="card-body prose article-body"><?= MagicCMS\Core\Emoji::render($p['body']) /* sanitized on save */ ?></div>
  <?php if ($tags): ?><div class="card-body tag-row"><?php foreach ($tags as $t): ?><a class="tag" href="<?= e(url('news/tag/' . $t['slug'])) ?>">#<?= e($t['name']) ?></a><?php endforeach ?></div><?php endif ?>
</article>
<?php if (can('admin.news')): ?><p><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/news/' . (int) $p['id'])) ?>">✎ <?= e(t('Edit post')) ?></a></p><?php endif ?>

<?php if ($p['comments_enabled']): ?>
<section class="card" id="comments"><h2 class="card-title"><?= e(t('Comments')) ?> <small>(<?= count($comments) ?>)</small></h2><div class="card-body">
  <?php foreach ($comments as $c): ?>
    <div class="comment"><img class="avatar avatar-md" src="<?= e(avatar_url(['id' => $c['user_id'], 'avatar' => $c['avatar']], 64)) ?>" alt="" width="48" height="48" loading="lazy">
      <div><div class="comment-head"><a class="role-<?= e($c['role_slug']) ?>" href="<?= e(url('user/' . $c['username'])) ?>"><strong><?= e($c['username']) ?></strong></a> <?= MagicCMS\Core\DisplayRoles::html((int) $c['user_id'], 'comments') ?> <span class="muted small"><?= e(time_ago($c['created_at'])) ?></span>
        <?php if ((can('news.moderate') && MagicCMS\Core\Auth::level() >= (int) $c['role_level']) || (user() && (int) user()['id'] === (int) $c['user_id'])): ?><form method="post" action="<?= e(url('news/comment/' . (int) $c['id'] . '/delete')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this comment?')) ?>">✕</button></form><?php endif ?></div>
        <p><?= MagicCMS\Core\Emoji::render(nl2br(e($c['body']))) ?></p></div></div>
  <?php endforeach; if (!$comments): ?><p class="muted"><?= e(t('No comments yet.')) ?></p><?php endif ?>
  <?php if (can('news.comment')): ?>
    <form method="post" action="<?= e(url('news/' . (int) $p['id'] . '/comment')) ?>" class="mt"><?= csrf_field() ?>
      <div class="form-row"><label class="label" for="cb"><?= e(t('Add a comment')) ?></label><textarea class="textarea" data-emoji id="cb" name="body" rows="3" maxlength="1000" required></textarea></div>
      <button class="btn btn-primary"><?= e(t('Post comment')) ?></button></form>
  <?php else: ?><p class="muted mt"><a href="<?= e(url('login?next=' . rawurlencode('/news/' . $p['slug']))) ?>"><?= e(t('Log in')) ?></a> <?= e(t('to comment.')) ?></p><?php endif ?>
</div></section>
<?php endif ?>

<?php if ($related): ?><h2 class="section-title"><?= e(t('More in this category')) ?></h2><div class="news-grid"><?php foreach ($related as $r): echo render('news/_card', ['p' => $r]); endforeach ?></div><?php endif ?>
