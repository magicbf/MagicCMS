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



<div class="stat-grid">
  <div class="stat-tile"><span><?= e(t('Members')) ?></span><strong><?= $members ?></strong></div>
  <div class="stat-tile is-hot"><span><?= e(t('Online now')) ?></span><strong><?= $online ?></strong></div>
  <?php if ($forum): ?>
  <div class="stat-tile"><span><?= e(t('Forum topics')) ?></span><strong><?= $topics ?></strong></div>
  <div class="stat-tile"><span><?= e(t('Forum posts')) ?></span><strong><?= $posts ?></strong></div>
  <?php endif ?>
</div>

<?php $news = db()->val("SELECT 1 FROM {modules} WHERE module_key = 'news' AND enabled = 1") ? MagicCMS\Modules\News\News::latest(6) : []; ?>
<?php if ($news): ?>
<section class="home-news"><div class="section-head"><h2 class="section-title"><?= e(t('Latest news')) ?></h2><a class="btn btn-ghost btn-sm" href="<?= e(url('news')) ?>"><?= e(t('All news')) ?> →</a></div>
  <div class="news-grid"><?php foreach ($news as $p): echo render('news/_card', ['p' => $p]); endforeach ?></div></section>
<?php endif ?>
<?php if (can('admin.access')): ?>
<div class="card"><h2 class="card-title"><?= e(t('Getting started')) ?></h2><div class="card-body">
  <ul class="checklist">
    <li><a href="<?= e(url('admin/appearance')) ?>"><?= e(t('Pick a colour scheme & light/dark default')) ?></a></li>
    <li><a href="<?= e(url('admin/settings')) ?>"><?= e(t('Upload your logo')) ?></a></li>
    <li><a href="<?= e(url('admin/navigation')) ?>"><?= e(t('Add buttons to the menu')) ?></a></li>
    <li><a href="<?= e(url('admin/panels')) ?>"><?= e(t('Arrange side panels (left / right / none)')) ?></a></li>
    <li><a href="<?= e(url('admin/hero')) ?>"><?= e(t('Customise the hero banner')) ?></a></li>
    <li><a href="<?= e(url('admin/news')) ?>"><?= e(t('Write your first news post')) ?></a></li>
    <li><a href="<?= e(url('admin/forum')) ?>"><?= e(t('Set up forum categories')) ?></a></li>
  </ul></div></div>
<?php endif ?>
