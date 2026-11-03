<?php /** @var array $rows @var int $page @var int $pages @var int $total */ ?>
<div class="card"><h1 class="card-title"><?= e(t('Members')) ?> <small>(<?= (int) $total ?>)</small></h1>
<div class="card-body member-grid">
<?php foreach ($rows as $m): ?>
  <a class="member-tile" href="<?= e(url('user/' . $m['username'])) ?>">
    <img class="avatar avatar-lg" src="<?= e(avatar_url($m, 128)) ?>" alt="" loading="lazy" width="64" height="64">
    <strong class="role-<?= e($m['role_slug']) ?>"><?= e($m['username']) ?></strong>
    <span class="muted"><?= e($m['role_name']) ?></span>
    <?= MagicCMS\Core\DisplayRoles::html((int) $m['id'], 'members') ?>
  </a>
<?php endforeach ?>
</div></div>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('members')]) ?>
