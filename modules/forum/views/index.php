<?php /** @var array $cats @var array $tree @var array $stats */ ?>
<div class="forum-head"><h1 class="admin-title"><?= e(t('Forum')) ?></h1></div>
<?= render('forum/_bar') ?>
<?php $any = false; foreach ($cats as $c): $top = $tree['top'][(int) $c['id']] ?? []; if (!$top) continue; $any = true; ?>
<section class="card forum-cat"><h2 class="card-title"><?= e($c['title']) ?></h2>
  <div class="forum-table">
  <?php foreach ($top as $b): ?>
    <div class="forum-row board-row<?= $b['unread'] ? ' is-unread' : '' ?>">
      <div class="fr-icon" title="<?= e($b['unread'] ? t('New posts') : '') ?>"><?= $b['is_locked'] ? '🔒' : ($b['unread'] ? '●' : '○') ?></div>
      <div class="fr-main">
        <a class="topic-title" href="<?= e(url('forum/board/' . (int) $b['id'])) ?>"><?= e($b['title']) ?></a>
        <?php if ($b['description'] !== ''): ?><div class="muted small"><?= e($b['description']) ?></div><?php endif ?>
        <?php if (!empty($tree['sub'][(int) $b['id']])): ?><div class="subboards small"><?= e(t('Sub-boards')) ?>: <?php foreach ($tree['sub'][(int) $b['id']] as $i => $sb): ?><?= $i ? ', ' : '' ?><a href="<?= e(url('forum/board/' . (int) $sb['id'])) ?>"><?= e($sb['title']) ?></a><?php endforeach ?></div><?php endif ?>
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
<?php endforeach; if (!$any): ?><div class="card"><div class="card-body muted"><?= e(t('There are no boards yet.')) ?> <?php if (can('admin.forum')): ?><a href="<?= e(url('admin/forum')) ?>"><?= e(t('Create one')) ?></a><?php endif ?></div></div><?php endif ?>
<p class="muted small center"><?= e(t('{t} topics · {p} posts · {m} members', ['t' => $stats['topics'], 'p' => $stats['posts'], 'm' => $stats['members']])) ?></p>
