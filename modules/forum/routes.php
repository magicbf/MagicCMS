<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Modules\Forum\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['forum'] = ['Forum', '/forum'];
        return $t;
    });

    // Forum structure: Category → Subcategory → Topic → Post
    $c = Controller::class;
    $w = ['auth' => true];
    $r->get('/forum', [$c, 'index']);
    $r->get('/forum/category/{id:\d+}', [$c, 'category']);
    $r->get('/forum/subcategory/{id:\d+}', [$c, 'board']);
    $r->get('/forum/board/{id:\d+}', [$c, 'legacyBoard']); // old URL → subcategory
    $r->get('/forum/topic/{id:\d+}', [$c, 'topic']);
    $r->get('/forum/topic/{id:\d+}/last', [$c, 'lastPost']);
    $r->get('/forum/post/{id:\d+}', [$c, 'jump']);
    $r->get('/forum/search', [$c, 'search']);
    $r->get('/forum/unread', [$c, 'unread'], $w);
    $r->post('/forum/markread', [$c, 'markRead'], $w);
    $r->post('/forum/preview', [$c, 'preview'], $w);

    $r->any('/forum/subcategory/{id:\d+}/new', [$c, 'newTopic'], $w);
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
    $r->post('/admin/forum/layout', [$a, 'saveLayout'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/category', [$a, 'saveCategory'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/category/delete/{id:\d+}', [$a, 'deleteCategory'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/subcategory', [$a, 'saveSubcategory'], ['perm' => 'admin.forum']);
    $r->post('/admin/forum/subcategory/delete/{id:\d+}', [$a, 'deleteSubcategory'], ['perm' => 'admin.forum']);
};
