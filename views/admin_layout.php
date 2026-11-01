<?php /** @var string $title @var string $content @var string $mode @var string $modeSetting @var string $head */
$u = user(); $site = setting('site.name', 'MagicCMS'); ?>
<!doctype html>
<html lang="<?= e(setting('site.lang', 'en')) ?>" data-theme="<?= e($mode) ?>" data-theme-setting="<?= e($modeSetting) ?>" data-motion="<?= e(MagicCMS\Core\Appearance::motion()) ?>" data-admin="1">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($title) ?> · <?= e(t('Admin')) ?> · <?= e($site) ?></title>
<meta name="color-scheme" content="dark light">
<link rel="icon" href="<?= e(site_logo()) ?>">
<script nonce="<?= e(MagicCMS\Core\Bootstrap::$nonce) ?>">(function(){var d=document.documentElement;if(d.dataset.themeSetting==='auto'){d.dataset.theme=matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'}})();</script>
<?= theme_assets('head', true) ?>
<?= $head ?>
</head>
<body class="mcms page-admin adm" data-csrf="<?= e(MagicCMS\Core\Csrf::token()) ?>" data-base="<?= e(url('')) ?>">
<div class="adm-bg" aria-hidden="true"><i class="o1"></i><i class="o2"></i><i class="o3"></i></div>
<a class="skip-link" href="#main"><?= e(t('Skip to content')) ?></a>
<div class="adm-shell">
  <?= $content /* _wrap renders sidebar + topbar + page inside .adm-shell via the admin/_wrap view */ ?>
</div>
<?= theme_assets('foot', true) ?>
</body>
</html>
