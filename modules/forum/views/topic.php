<?php /** @var array $t @var array $posts @var int $page @var int $pages @var array $moveTargets @var bool $canReply @var bool $subscribed @var array $atts @var array $reacts @var ?array $poll */
use MagicCMS\Core\Bbcode; use MagicCMS\Modules\Forum\Forum;
$me = user(); $mod = Forum::isMod(); $modTopic = $mod && MagicCMS\Core\Auth::level() >= (int) $t['author_level']; $base = url('forum/topic/' . (int) $t['id']); ?>
<?= render('forum/_crumbs', ['items' => [[$t['category_title'], url('forum/category/' . (int) $t['category_id'])], [$t['board_title'], url('forum/subcategory/' . (int) $t['board_id'])], [$t['title'], null]]]) ?>
<div class="forum-head"><h1 class="admin-title"><?= $t['is_locked'] ? '🔒 ' : '' ?><?= e($t['title']) ?></h1>
  <div class="forum-head-actions"><?php if ($me): ?><form class="inline" method="post" action="<?= e($base . '/subscribe') ?>"><?= csrf_field() ?><input type="hidden" name="sub" value="<?= $subscribed ? '0' : '1' ?>"><button class="btn btn-ghost" title="<?= e($subscribed ? t('Stop notifications') : t('Get notified about new replies')) ?>"><?= $subscribed ? '🔔 ' . e(t('Following')) : '🔕 ' . e(t('Follow')) ?></button></form><?php endif ?>
  <?php if ($canReply): ?><a class="btn btn-primary" href="<?= e($base . '/reply') ?>">↩ <?= e(t('Reply')) ?></a><?php elseif ($t['is_locked']): ?><span class="badge">🔒 <?= e(t('Locked')) ?></span><?php elseif (!$me): ?><a class="btn btn-ghost" href="<?= e(url('login?next=' . rawurlencode('/forum/topic/' . (int) $t['id']))) ?>"><?= e(t('Log in to reply')) ?></a><?php endif ?></div></div>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => $base]) ?>
<?php if ($poll && $page === 1): ?><?= render('forum/_poll', ['poll' => $poll, 't' => $t]) ?><?php endif ?>
<?php foreach ($posts as $i => $p): $own = $me && (int) $me['id'] === (int) $p['user_id']; $first = (int) $p['id'] === (int) $t['first_post_id']; ?>
<article class="post card" id="post-<?= (int) $p['id'] ?>">
  <aside class="post-author">
    <a href="<?= e(url('user/' . $p['username'])) ?>"><img class="avatar avatar-xl" src="<?= e(avatar_url(['id' => $p['user_id'], 'avatar' => $p['avatar']], 128)) ?>" alt="" width="96" height="96" loading="lazy"></a>
    <a class="author-name role-<?= e($p['role_slug']) ?>" href="<?= e(url('user/' . $p['username'])) ?>"><?= e($p['username']) ?></a>
    <span class="badge role-<?= e($p['role_slug']) ?>"><?= e($p['role_name']) ?></span>
    <?= MagicCMS\Core\DisplayRoles::html((int) $p['user_id'], 'forum') ?>
    <span class="muted small"><?= e(Forum::rank((int) $p['forum_posts'])) ?></span>
    <span class="muted small"><?= e(t('Posts')) ?>: <?= (int) $p['forum_posts'] ?></span>
    <span class="muted small"><?= e(t('Joined')) ?> <?= e(fmt_date($p['joined'], 'Y-m-d')) ?></span>
  </aside>
  <div class="post-main">
    <div class="post-meta"><a href="<?= e(Forum::postUrl((int) $p['id'])) ?>">#<?= (int) $p['id'] ?></a> · <?= e(fmt_date($p['created_at'])) ?><?= $first ? ' · <span class="badge">' . e(t('Topic starter')) . '</span>' : '' ?></div>
    <div class="post-body"><?= Bbcode::render($p['body']) ?></div>
    <?php if (!empty($atts[(int) $p['id']])): ?><?= render('forum/_attachments', ['list' => $atts[(int) $p['id']]]) ?><?php endif ?>
    <?= render('forum/_reactions', ['post' => $p, 'r' => $reacts[(int) $p['id']] ?? [], 'own' => $own]) ?>
    <?php if ($p['edited_at']): ?><div class="muted small post-edited"><?= e(t('Edited {when} by {who}', ['when' => time_ago($p['edited_at']), 'who' => $p['edited_by_name'] ?? '?'])) ?></div><?php endif ?>
    <?php if ($p['signature'] !== ''): ?><div class="post-signature"><?= e($p['signature']) ?></div><?php endif ?>
    <div class="post-actions">
      <?php if ($canReply): ?><a class="btn btn-sm btn-ghost" href="<?= e($base . '/reply?quote=' . (int) $p['id']) ?>"><?= e(t('Quote')) ?></a><?php endif ?>
      <?php $modPost = $mod && MagicCMS\Core\Auth::level() >= (int) $p['role_level']; ?><?php if ($modPost || ($own && !$t['is_locked'] && can('forum.post'))): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('forum/post/' . (int) $p['id'] . '/edit')) ?>"><?= e(t('Edit')) ?></a><?php endif ?>
      <?php if ($modPost || ($own && !$first && !$t['is_locked'])): ?><form method="post" action="<?= e(url('forum/post/' . (int) $p['id'] . '/delete')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e($first ? t('Delete the whole topic?') : t('Delete this post?')) ?>"><?= e(t('Delete')) ?></button></form><?php endif ?>
      <?php if ($me && !$own && can('forum.report')): ?><details class="report"><summary class="btn btn-sm btn-ghost"><?= e(t('Report')) ?></summary>
        <form method="post" action="<?= e(url('forum/post/' . (int) $p['id'] . '/report')) ?>"><?= csrf_field() ?><input class="input" name="reason" maxlength="300" placeholder="<?= e(t('What is wrong with this post?')) ?>" required><button class="btn btn-sm btn-primary"><?= e(t('Send report')) ?></button></form></details><?php endif ?>
    </div>
  </div>
</article>
<?php endforeach ?>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => $base]) ?>

<?php if ($canReply && $page === $pages): ?>
<form class="card quick-reply" method="post" action="<?= e($base . '/reply') ?>" enctype="multipart/form-data"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Quick reply')) ?></h2>
  <div class="card-body"><?= render('forum/_editor', ['body' => '', 'rows' => 5]) ?><?= render('forum/_attach_field', ['atts' => []]) ?><button class="btn btn-primary"><?= e(t('Post reply')) ?></button></div></form>
<?php endif ?>

<?php if ($modTopic): ?>
<div class="card mod-tools"><h2 class="card-title">🛡 <?= e(t('Moderator tools')) ?></h2><div class="card-body inline-form">
  <?php foreach (($t['is_locked'] ? ['unlock' => 'Unlock'] : ['lock' => 'Lock']) + ($t['type'] !== 'sticky' ? ['sticky' => 'Make sticky'] : []) + ($t['type'] !== 'announce' ? ['announce' => 'Make announcement'] : []) + ($t['type'] !== 'normal' ? ['normal' => 'Make normal'] : []) as $act => $label): ?>
    <form method="post" action="<?= e($base . '/mod') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="<?= e($act) ?>"><button class="btn btn-sm btn-ghost"><?= e(t($label)) ?></button></form>
  <?php endforeach ?>
  <form method="post" action="<?= e($base . '/mod') ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="move"><select class="select" name="board_id" aria-label="<?= e(t('Move to')) ?>"><?php foreach ($moveTargets as $mt): ?><option value="<?= (int) $mt['id'] ?>" <?= (int) $mt['id'] === (int) $t['board_id'] ? 'selected' : '' ?>><?= e($mt['title']) ?></option><?php endforeach ?></select><button class="btn btn-sm btn-ghost"><?= e(t('Move')) ?></button></form>
  <form method="post" action="<?= e($base . '/mod') ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete the whole topic?')) ?>"><?= e(t('Delete topic')) ?></button></form>
</div></div>
<?php endif ?>
