<?php /** @var array $menu @var ?array $item @var array $tops @var array $pages @var array $targets */ ?>
<?= render('admin/_nav_form', compact('menu', 'item', 'tops', 'pages', 'targets')) ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/navigation?menu=' . $menu['menu_key'])) ?>">← <?= e(t('Back')) ?></a></p>
