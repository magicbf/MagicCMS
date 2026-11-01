<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Hero\AdminController;

return function (Router $r): void {
    $a = AdminController::class;
    $p = ['perm' => 'admin.hero'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/hero', [$a, 'index'], $l);
    $r->post('/admin/hero/settings', [$a, 'saveSettings'], $p);
    $r->get('/admin/hero/slide/new', [$a, 'edit'], $l);
    $r->get('/admin/hero/slide/{id:\d+}', [$a, 'edit'], $l);
    $r->post('/admin/hero/slide/save', [$a, 'saveSlide'], $p);
    $r->post('/admin/hero/slide/delete/{id:\d+}', [$a, 'deleteSlide'], $p);
    $r->post('/admin/hero/slide/toggle/{id:\d+}', [$a, 'toggleSlide'], $p);
    $r->post('/admin/hero/slide/move/{id:\d+}/{dir:up|down}', [$a, 'moveSlide'], $p);
};
