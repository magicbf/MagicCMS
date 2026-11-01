<div class="forum-bar">
  <div class="forum-bar-links">
    <?php if (user()): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('forum/unread')) ?>"><?= e(t('Unread topics')) ?></a>
      <form method="post" action="<?= e(url('forum/markread')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" type="submit"><?= e(t('Mark all read')) ?></button></form><?php endif ?>
    <?php if (can('forum.moderate')): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('forum/reports')) ?>">🛡 <?= e(t('Reports')) ?><?php $n = (int) db()->val("SELECT COUNT(*) FROM {forum_reports} WHERE status = 'open'"); if ($n): ?> <span class="badge"><?= $n ?></span><?php endif ?></a><?php endif ?>
  </div>
  <form class="forum-search" method="get" action="<?= e(url('forum/search')) ?>"><input class="input" name="q" value="<?= e($q ?? '') ?>" placeholder="<?= e(t('Search the forum…')) ?>" aria-label="<?= e(t('Search')) ?>" minlength="3"><button class="btn btn-sm btn-primary"><?= e(t('Search')) ?></button></form>
</div>
