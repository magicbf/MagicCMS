<?php /** @var array $rows @var array $roles @var string $q @var int $page @var int $pages @var int $total */
$me = user(); ?>
<form class="searchbar" method="get" action="<?= e(url('admin/users')) ?>"><input class="input" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('Search username or email…')) ?>"><button class="btn btn-ghost"><?= e(t('Search')) ?></button></form>
<div class="card"><h2 class="card-title"><?= e(t('Users')) ?> <small>(<?= (int) $total ?>)</small></h2><div class="card-body table-wrap">
<table class="table"><thead><tr><th><?= e(t('User')) ?></th><th><?= e(t('Email')) ?></th><th><?= e(t('Role')) ?></th><th><?= e(t('Status')) ?></th><th><?= e(t('Last login')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $can = MagicCMS\Core\Auth::canManage($r); ?>
<tr><td><a href="<?= e(url('user/' . $r['username'])) ?>" class="role-<?= e($r['role_slug']) ?>"><?= e($r['username']) ?></a></td><td><?= e($r['email']) ?> <?php if (empty($r['email_verified_at'])): ?><span class="badge" title="<?= e(t('E-mail not verified')) ?>">!</span><?php if ($can): ?><form class="inline" method="post" action="<?= e(url('admin/users/' . (int) $r['id'] . '/verify')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e(t('Verify')) ?></button></form><?php endif ?><?php endif ?></td>
<?php if ($can): ?>
  <td colspan="3"><form class="inline-form" method="post" action="<?= e(url('admin/users/' . (int) $r['id'])) ?>"><?= csrf_field() ?>
    <select class="select" name="role_id" aria-label="Role"><?php foreach ($roles as $ro): ?><option value="<?= (int) $ro['id'] ?>" <?= (int) $ro['id'] === (int) $r['role_id'] ? 'selected' : '' ?>><?= e($ro['name']) ?></option><?php endforeach ?></select>
    <select class="select" name="status" aria-label="Status"><?php foreach (['active', 'pending', 'banned'] as $s): ?><option <?= $r['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach ?></select>
    <span class="muted"><?= e(time_ago($r['last_login'])) ?></span><button class="btn btn-sm btn-primary"><?= e(t('Save')) ?></button></form></td><td></td>
<?php else: ?>
  <td><?= e($r['role_name']) ?></td><td><span class="badge"><?= e($r['status']) ?></span></td><td><?= e(time_ago($r['last_login'])) ?></td><td class="muted"><?= (int) $r['id'] === (int) $me['id'] ? e(t('you')) : '🔒' ?></td>
<?php endif ?></tr>
<?php endforeach ?></tbody></table></div></div>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('admin/users') . ($q !== '' ? '?q=' . rawurlencode($q) : '')]) ?>
