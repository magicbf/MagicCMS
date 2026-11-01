<?php /** @var array $cats @var array $tree [categoryId => subcategories] @var ?array $stats @var ?array $focus */ ?>
<?php if ($focus): ?><?= render('forum/_crumbs', ['items' => [[$focus['title'], null]]]) ?><?php endif ?>
<div class="forum-head"><h1 class="admin-title"><?= e($focus ? $focus['title'] : t('Forum')) ?></h1></div>
<?= render('forum/_bar') ?>
<?php $any = false; foreach ($cats as $c): $subs = $tree[(int) $c['id']] ?? []; if (!$subs) continue; $any = true; ?>
<section class="card forum-cat"><h2 class="card-title"><a class="cat-link" href="<?= e(url('forum/category/' . (int) $c['id'])) ?>"><?= e($c['title']) ?></a></h2>
  <div class="forum-table">
  <?php foreach ($subs as $b): ?>
    <div class="forum-row board-row<?= $b['unread'] ? ' is-unread' : '' ?>">
      <div class="fr-icon" title="<?= e($b['unread'] ? t('New posts') : '') ?>"><?= $b['is_locked'] ? '🔒' : ($b['unread'] ? '●' : '○') ?></div>
      <div class="fr-main">
        <a class="topic-title" href="<?= e(url('forum/subcategory/' . (int) $b['id'])) ?>"><?= e($b['title']) ?></a>
        <?php if ($b['description'] !== ''): ?><div class="muted small"><?= e($b['description']) ?></div><?php endif ?>
      </div>
      <div class="fr-stat"><strong><?= (int) $b['topic_count'] ?></strong><span><?= e(t('topics')) ?></span></div>
      <div class="fr-stat hide-mobile"><strong><?= (int) $b['post_count'] ?></strong><span><?= e(t('posts')) ?></span></div>
      <div class="fr-last">
        <?php if ($b['last']): $l = $b['last']; ?>
          <img class="avatar avatar-sm" src="<?= e(avatar_url(['id' => $l['uid'], 'avatar' => $l['avatar']], 32)) ?>" alt="" width="32" height="32" loading="lazy">
          <div><a class="clip" href="<?= e(MagicCMS\Modules\Forum\Forum::postUrl((int) $l['id'])) ?>"><?= e($l['title']) ?></a><div class="muted small"><?= e(t('by')) ?> <a href="<?= e(url('user/' . $l['username'])) ?>"><?= e($l['username']) ?></a> · <?= e(time_ago($l['created_at'])) ?></div></div>
        <?php else: ?><span class="muted"><?= e(t('No posts yet')) ?></span><?php endif ?>
      </div>
    </div>
  <?php endforeach ?>
  </div>
</section>
<?php endforeach; if (!$any): ?><div class="card"><div class="card-body muted"><?= e(t('There are no categories yet.')) ?> <?php if (can('admin.forum')): ?><a href="<?= e(url('admin/forum')) ?>"><?= e(t('Create one')) ?></a><?php endif ?></div></div><?php endif ?>
<?php if ($stats): ?><p class="muted small center"><?= e(t('{t} topics · {p} posts · {m} members', ['t' => $stats['topics'], 'p' => $stats['posts'], 'm' => $stats['members']])) ?></p><?php endif ?>
