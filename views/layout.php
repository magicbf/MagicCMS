<?php /** @var string $title @var string $content @var array $layout @var string $bodyClass @var string $description @var string $mode @var string $modeSetting @var string $hero @var string $head */ ?>
<!doctype html>
<html lang="<?= e(setting('site.lang', 'en')) ?>" data-theme="<?= e($mode) ?>" data-theme-setting="<?= e($modeSetting) ?>" data-motion="<?= e(MagicCMS\Core\Appearance::motion()) ?>" data-nav-style="<?= e(setting('nav.button_style', 'gradient')) ?>" data-skin="<?= e(preg_replace('/[^a-z0-9\-]/', '', (string) setting('theme.active', 'magic-red'))) ?>" data-header="<?= e(MagicCMS\Core\Layout::header()) ?>" data-rhythm="<?= e(MagicCMS\Core\Layout::rhythm()) ?>" data-nav-fx="<?= e(setting('nav.hover_fx', 'shine')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e(setting('site.name', 'MagicCMS')) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="color-scheme" content="dark light">
<link rel="icon" href="<?= e(site_logo()) ?>">
<script nonce="<?= e(MagicCMS\Core\Bootstrap::$nonce) ?>">(function(){var d=document.documentElement;if(d.dataset.themeSetting==='auto'){d.dataset.theme=matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'}})();</script>
<?= theme_assets('head') ?>
<?= $head ?>
<?= hook('layout.render.head') ?>
</head>
<body class="mcms layout-<?= e($layout['mode']) ?> <?= e($bodyClass) ?>" data-csrf="<?= e(MagicCMS\Core\Csrf::token()) ?>" data-base="<?= e(url('')) ?>">
<div class="bg-fx" aria-hidden="true"><span class="fx-a"></span><span class="fx-b"></span><span class="fx-c"></span><span class="fx-grid"></span><span class="fx-grain"></span><span class="fx-vig"></span></div>
<a class="skip-link" href="#main"><?= e(t('Skip to content')) ?></a>
<?= render('header', ['layout' => $layout]) ?>
<?= $hero ?>
<div class="container">
<?php foreach (flashes() as [$type, $msg]): ?><div class="alert alert-<?= e($type) ?>" role="status"><?= e($msg) ?></div><?php endforeach ?>
<div class="site-grid">
<?php if ($layout['left']): ?><aside class="col-left" id="drawer-left" aria-label="<?= e(t('Left panels')) ?>"><?= implode("\n", $layout['left']) ?></aside><?php endif ?>
<main class="col-main" id="main"><?= $content ?></main>
<?php if ($layout['right']): ?><aside class="col-right" id="drawer-right" aria-label="<?= e(t('Right panels')) ?>"><?= implode("\n", $layout['right']) ?></aside><?php endif ?>
</div>
</div>
<?php if (!empty($layout['above']) && !str_contains($bodyClass, 'page-admin') && !str_contains($bodyClass, 'page-auth')): ?><div class="above-footer"><?= implode("\n", $layout['above']) ?></div><?php endif ?>
<?= render('footer') ?>
<?= hook('layout.render.footer') ?>
<?= theme_assets('foot') ?>
</body>
</html>
