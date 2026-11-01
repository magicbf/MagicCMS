<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Plugins\Events\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['events'] = ['Events', '/events'];
        return $t;
    });
    Hooks::on('admin.tabs', static function (array $tabs): array {
        $tabs['events'] = ['/admin/events', 'Events', 'admin.events'];
        return $tabs;
    });

    $c = Controller::class;
    $r->get('/events', [$c, 'index']);
    $r->get('/events/feed.ics', [$c, 'feed']);
    $r->get('/events/{id:\d+}.ics', [$c, 'ics']);
    $r->get('/events/{id:\d+}', [$c, 'show']);
    $r->post('/events/{id:\d+}/signup', [$c, 'signup'], ['auth' => true, 'perm' => 'events.signup']);

    $a = AdminController::class;
    $p = ['perm' => 'admin.events'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/events', [$a, 'index'], $l);
    $r->get('/admin/events/new', [$a, 'edit'], $l);
    $r->get('/admin/events/{id:\d+}', [$a, 'edit'], $l);
    $r->post('/admin/events/save', [$a, 'save'], $p);
    $r->post('/admin/events/delete/{id:\d+}', [$a, 'delete'], $p);
    $r->post('/admin/events/{id:\d+}/remove/{uid:\d+}', [$a, 'removeSignup'], $p);
    $r->post('/admin/events/{id:\d+}/bracket', [$a, 'bracket'], $p);
};
