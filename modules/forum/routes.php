<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Modules\Forum\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['forum'] = ['Forum', '/forum'];
        return $t;
    });

    $c = Controller::class;
    $w = ['auth' => true];
    $r->get('/forum', [$c, 'index'], ['layout' => 'none']);
    $r->get('/forum/board/{id:\d+}', [$c, 'board']);
    $r->get('/forum/topic/{id:\d+}', [$c, 'topic']);
    $r->get('/forum/topic/{id:\d+}/last', [$c, 'lastPost']);
    $r->get('/forum/post/{id:\d+}', [$c, 'jump']);
    $r->get('/forum/search', [$c, 'search']);
    $r->get('/forum/unread', [$c, 'unread'], $w);
    $r->post('/forum/markread', [$c, 'markRead'], $w);
    $r->post('/forum/preview', [$c, 'preview'], $w);

    $r->any('/forum/board/{id:\d+}/new', [$c, 'newTopic'], $w);
    $r->any('/forum/topic/{id:\d+}/reply', [$c, 'reply'], $w);
    $r->any('/forum/post/{id:\d+}/edit', [$c, 'edit'], $w);
    $r->post('/forum/post/{id:\d+}/delete', [$c, 'deletePost'], $w);
    $r->post('/forum/post/{id:\d+}/report', [$c, 'report'], $w);
    $r->post('/forum/topic/{id:\d+}/mod', [$c, 'mod'], $w);
    $r->get('/forum/reports', [$c, 'reports'], ['perm' => 'forum.moderate']);
    $r->post('/forum/reports/{id:\d+}/close', [$c, 'closeReport'], ['perm' => 'forum.moderate']);

    $a = AdminController::class;
    $o = ['perm' => 'admin.forum', 'layout' => 'none'];
    $r->get('/admin/forum', [$a, 'index'], $o);
    $r->post('/admin/forum/category', [$a, 'saveCategory'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/category/delete/{id:\d+}', [$a, 'deleteCategory'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/board', [$a, 'saveBoard'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/board/delete/{id:\d+}', [$a, 'deleteBoard'], ['perm' => 'admin.forum']);
};
