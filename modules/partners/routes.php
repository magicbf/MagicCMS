<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Modules\Partners\Controller;

return function (Router $r): void {
    Hooks::on('admin.tabs', static function (array $tabs): array {
        $tabs['partners'] = ['/admin/partners', 'Partners', 'admin.partners'];
        return $tabs;
    });
    $c = Controller::class;
    $p = ['perm' => 'admin.partners'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/partners', [$c, 'index'], $l);
    $r->get('/admin/partners/new', [$c, 'edit'], $l);
    $r->get('/admin/partners/{id:\d+}', [$c, 'edit'], $l);
    $r->post('/admin/partners/save', [$c, 'save'], $p);
    $r->post('/admin/partners/delete/{id:\d+}', [$c, 'delete'], $p);
    $r->post('/admin/partners/toggle/{id:\d+}', [$c, 'toggle'], $p);
    $r->post('/admin/partners/move/{id:\d+}/{dir:up|down}', [$c, 'move'], $p);
};
