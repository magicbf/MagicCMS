<?php /** @var array $t topic row incl. author, lp_* , is_unread */
$pages = (int) ceil(((int) $t['reply_count'] + 1) / MagicCMS\Modules\Forum\Forum::PER_PAGE_POSTS); ?>
<div class="forum-row topic-row<?= $t['is_unread'] ? ' is-unread' : '' ?>">
  <div class="fr-icon" title="<?= e($t['is_locked'] ? t('Locked') : ($t['is_unread'] ? t('New posts') : '')) ?>"><?= $t['is_locked'] ? '🔒' : ($t['type'] === 'announce' ? '📢' : ($t['type'] === 'sticky' ? '📌' : ($t['is_unread'] ? '●' : '○'))) ?></div>
  <div class="fr-main">
    <a class="topic-title" href="<?= e(url('forum/topic/' . (int) $t['id'])) ?>"><?= e($t['title']) ?></a>
    <?php if ($t['type'] !== 'normal'): ?><span class="badge"><?= e($t['type'] === 'announce' ? t('Announcement') : t('Sticky')) ?></span><?php endif ?>
    <?php if ($pages > 1): ?><span class="mini-pages"><?php for ($i = 1; $i <= min($pages, 4); $i++): ?><a href="<?= e(url('forum/topic/' . (int) $t['id'] . '?page=' . $i)) ?>"><?= $i ?></a><?php endfor ?><?= $pages > 4 ? '…<a href="' . e(url('forum/topic/' . (int) $t['id'] . '/last')) . '">' . $pages . '</a>' : '' ?></span><?php endif ?>
    <div class="muted small"><?= e(t('by')) ?> <a href="<?= e(url('user/' . $t['author'])) ?>"><?= e($t['author']) ?></a>, <?= e(time_ago($t['created_at'])) ?><?= isset($t['board_title']) ? ' · ' . e(t('in')) . ' <a href="' . e(url('forum/subcategory/' . (int) $t['board_id'])) . '">' . e($t['board_title']) . '</a>' : '' ?></div>
  </div>
  <div class="fr-stat"><strong><?= (int) $t['reply_count'] ?></strong><span><?= e(t('replies')) ?></span></div>
  <div class="fr-stat hide-mobile"><strong><?= (int) $t['views'] ?></strong><span><?= e(t('views')) ?></span></div>
  <div class="fr-last">
    <img class="avatar avatar-sm" src="<?= e(avatar_url(['id' => $t['lp_uid'], 'avatar' => $t['lp_avatar']], 32)) ?>" alt="" width="32" height="32" loading="lazy">
    <div><a href="<?= e(url('forum/topic/' . (int) $t['id'] . '/last')) ?>"><?= e(time_ago($t['lp_at'])) ?></a><div class="muted small"><?= e(t('by')) ?> <a href="<?= e(url('user/' . $t['lp_user'])) ?>"><?= e($t['lp_user']) ?></a></div></div>
  </div>
</div>
