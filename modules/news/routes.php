<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Modules\News\{AdminController, Controller};

return function (Router $r): void {
    Hooks::on('nav.targets', static function (array $t): array {
        $t['news'] = ['News', '/news'];
        return $t;
    });

    $c = Controller::class;
    $r->get('/news', [$c, 'index']);
    $r->get('/news/feed.xml', [$c, 'feed']);
    $r->get('/news/category/{slug:[a-z0-9\-]+}', [$c, 'category']);
    $r->get('/news/tag/{slug:[a-z0-9\-]+}', [$c, 'tag']);
    $r->get('/news/{slug:[a-z0-9\-]+}', [$c, 'show']);
    $r->post('/news/{id:\d+}/comment', [$c, 'comment'], ['auth' => true]);
    $r->post('/news/comment/{id:\d+}/delete', [$c, 'deleteComment'], ['auth' => true]);
    $r->get('/cover/{kind:news|cat}/{id:\d+}.svg', [$c, 'cover']);

    $a = AdminController::class;
    $p = ['perm' => 'admin.news'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/news', [$a, 'index'], $l);
    $r->get('/admin/news/new', [$a, 'edit'], $l);
    $r->get('/admin/news/categories', [$a, 'categories'], $l);
    $r->post('/admin/news/categories/save', [$a, 'saveCategory'], $p);
    $r->post('/admin/news/categories/delete/{id:\d+}', [$a, 'deleteCategory'], $p);
    $r->post('/admin/news/categories/style', [$a, 'saveStyle'], $p);
    $r->get('/admin/news/{id:\d+}', [$a, 'edit'], $l);
    $r->post('/admin/news/save', [$a, 'save'], $p);
    $r->post('/admin/news/delete/{id:\d+}', [$a, 'delete'], $p);
};
