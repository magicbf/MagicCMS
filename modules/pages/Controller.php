<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Pages;

use MagicCMS\Core\{Router, View};

final class Controller
{
    public function show(array $p): string
    {
        $page = db()->one('SELECT * FROM {pages} WHERE slug = ?', [$p['slug']]);
        if (!$page || (!$page['published'] && !can('admin.pages'))) {
            (new Router())->notFound();
            return '';
        }
        return View::page($page['title'], render('pages/show', ['page' => $page]), ['layout' => $page['layout'] ?: null, 'class' => 'page-custom']);
    }
}
