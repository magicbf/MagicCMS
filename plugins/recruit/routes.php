<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Plugins\Recruit\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['recruit'] = ['Join us (recruitment)', '/recruit'];
        return $t;
    });
    Hooks::on('admin.tabs', static function (array $tabs): array {
        $tabs['recruit'] = ['/admin/recruit', 'Recruitment', 'admin.recruit'];
        return $tabs;
    });

    $c = Controller::class;
    $r->get('/recruit', [$c, 'form']);
    $r->post('/recruit', [$c, 'submit']);
    $r->get('/recruit/my', [$c, 'mine'], ['auth' => true]);
    $r->get('/recruit/status/{token:[a-f0-9]+}', [$c, 'status']);
    $r->post('/recruit/status/{token:[a-f0-9]+}/withdraw', [$c, 'withdraw']);

    $a = AdminController::class;
    $p = ['perm' => 'admin.recruit'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/recruit', [$a, 'index'], $l);
    $r->get('/admin/recruit/setup', [$a, 'setup'], $l);
    $r->post('/admin/recruit/settings', [$a, 'saveSettings'], $p);
    $r->post('/admin/recruit/position', [$a, 'savePosition'], $p);
    $r->post('/admin/recruit/position/delete/{id:\d+}', [$a, 'deletePosition'], $p);
    $r->post('/admin/recruit/question', [$a, 'saveQuestion'], $p);
    $r->post('/admin/recruit/question/delete/{id:\d+}', [$a, 'deleteQuestion'], $p);
    $r->get('/admin/recruit/{id:\d+}', [$a, 'show'], $l);
    $r->post('/admin/recruit/{id:\d+}/status', [$a, 'setStatus'], $p);
    $r->post('/admin/recruit/{id:\d+}/note', [$a, 'note'], $p);
    $r->post('/admin/recruit/{id:\d+}/delete', [$a, 'delete'], $p);
};
