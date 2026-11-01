<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Plugins\Matches\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['matches'] = ['Matches', '/matches'];
        return $t;
    });
    Hooks::on('admin.tabs', static function (array $tabs): array {
        $tabs['matches'] = ['/admin/matches', 'Matches', 'admin.matches'];
        return $tabs;
    });

    $c = Controller::class;
    $r->get('/matches', [$c, 'index']);
    $r->get('/matches/{id:\d+}', [$c, 'show']);

    $a = AdminController::class;
    $p = ['perm' => 'admin.matches'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/matches', [$a, 'index'], $l);
    $r->get('/admin/matches/new', [$a, 'edit'], $l);
    $r->get('/admin/matches/{id:\d+}', [$a, 'edit'], $l);
    $r->post('/admin/matches/save', [$a, 'save'], $p);
    $r->post('/admin/matches/delete/{id:\d+}', [$a, 'delete'], $p);
    $r->post('/admin/matches/settings', [$a, 'saveSettings'], $p);
};
