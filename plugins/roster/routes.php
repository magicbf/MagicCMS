<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Plugins\Roster\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['roster'] = ['Roster', '/roster'];
        return $t;
    });
    Hooks::on('admin.tabs', static function (array $tabs): array {
        $tabs['roster'] = ['/admin/roster', 'Roster', 'admin.roster'];
        return $tabs;
    });

    $c = Controller::class;
    $r->get('/roster', [$c, 'index']);
    $r->get('/roster/{id:\d+}', [$c, 'show']);

    $a = AdminController::class;
    $p = ['perm' => 'admin.roster'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/roster', [$a, 'index'], $l);
    $r->get('/admin/roster/new', [$a, 'edit'], $l);
    $r->get('/admin/roster/{id:\d+}', [$a, 'edit'], $l);
    $r->post('/admin/roster/save', [$a, 'save'], $p);
    $r->post('/admin/roster/delete/{id:\d+}', [$a, 'delete'], $p);
    $r->post('/admin/roster/toggle/{id:\d+}', [$a, 'toggle'], $p);
    $r->post('/admin/roster/move/{id:\d+}/{dir:up|down}', [$a, 'move'], $p);
};
