<?php /** @var array $settings */
$items = MagicCMS\Core\Nav::tree(preg_replace('/[^a-z0-9_]/', '', (string) $settings['menu']));
if (!$items) return;
echo render('nav', ['items' => $items, 'class' => 'nav-vertical']); ?>
