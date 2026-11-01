<?php
$u = user();
$members = (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'");
$since = gmdate('Y-m-d H:i:s', time() - 60 * max(1, (int) setting('online.window_min', 5)));
$online = (int) db()->val('SELECT COUNT(*) FROM {online_sessions} WHERE last_seen > ?', [$since]);
$forum = (bool) db()->val("SELECT 1 FROM {modules} WHERE module_key = 'forum' AND enabled = 1");
$topics = $forum ? (int) db()->val('SELECT COUNT(*) FROM {forum_topics} t JOIN {forum_boards} b ON b.id = t.board_id WHERE b.read_level <= ?', [MagicCMS\Core\Auth::level()]) : 0;
$posts = $forum ? (int) db()->val('SELECT COUNT(*) FROM {forum_posts} p JOIN {forum_topics} t ON t.id = p.topic_id JOIN {forum_boards} b ON b.id = t.board_id WHERE b.read_level <= ?', [MagicCMS\Core\Auth::level()]) : 0;
?>
<div class="page-intro">
  <div>
    <span class="eyebrow"><?= e(setting('site.tagline', '')) ?></span>
    <h1 class="page-title"><?= $u ? e(t('Welcome back, {name}', ['name' => $u['username']])) : e(t('Welcome to')) . ' <span class="grad-text">' . e(setting('site.name', 'MagicCMS')) . '</span>' ?></h1>
  </div>
  <span class="live-dot"><i></i><?= e(t('{n} online now', ['n' => $online])) ?></span>
</div>

<section class="hero-lite">
  <svg class="hero-mark" aria-hidden="true" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="2"><path d="M42 150V62q0-10 10-4l48 42 48-42q10-6 10 4v88h-24V96l-34 30-34-30v54z"/><path d="M100 24l12 22-12 22-12-22z"/></svg>
  <div class="hero-lite-inner">
    <span class="eyebrow"><?= e(t('Official website')) ?></span>
    <h2 class="hero-title"><?= e((string) setting('site.tagline', '') !== '' ? setting('site.tagline') : setting('site.name', 'MagicCMS')) ?></h2>
    <p class="lead"><?= e(setting('site.description', '')) ?></p>
    <div class="hero-cta">
      <?php if (!$u && setting('auth.registration', 'open') !== 'closed'): ?><a class="btn btn-dark" href="<?= e(url('register')) ?>"><?= e(t('Join the team')) ?> <span aria-hidden="true">→</span></a><?php endif ?>
      <?php if ($forum): ?><a class="btn btn-glass" href="<?= e(url('forum')) ?>"><?= e(t('Open the forum')) ?></a><?php endif ?>
      <a class="btn btn-glass" href="<?= e(url('members')) ?>"><?= e(t('Meet the members')) ?></a>
    </div>
  </div>
</section>

<div class="stat-grid">
  <div class="stat-tile"><span><?= e(t('Members')) ?></span><strong><?= $members ?></strong></div>
  <div class="stat-tile is-hot"><span><?= e(t('Online now')) ?></span><strong><?= $online ?></strong></div>
  <?php if ($forum): ?>
  <div class="stat-tile"><span><?= e(t('Forum topics')) ?></span><strong><?= $topics ?></strong></div>
  <div class="stat-tile"><span><?= e(t('Forum posts')) ?></span><strong><?= $posts ?></strong></div>
  <?php endif ?>
</div>

<?php if (can('admin.access')): ?>
<div class="card"><h2 class="card-title"><?= e(t('Getting started')) ?></h2><div class="card-body">
  <ul class="checklist">
    <li><a href="<?= e(url('admin/appearance')) ?>"><?= e(t('Pick a colour scheme & light/dark default')) ?></a></li>
    <li><a href="<?= e(url('admin/settings')) ?>"><?= e(t('Upload your logo')) ?></a></li>
    <li><a href="<?= e(url('admin/navigation')) ?>"><?= e(t('Add buttons to the menu')) ?></a></li>
    <li><a href="<?= e(url('admin/panels')) ?>"><?= e(t('Arrange side panels (left / right / none)')) ?></a></li>
    <li><a href="<?= e(url('admin/forum')) ?>"><?= e(t('Set up forum boards')) ?></a></li>
  </ul></div></div>
<?php endif ?>
