<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Members;

use MagicCMS\Core\View;

final class Controller
{
    public function index(): string
    {
        $per = 24;
        $total = (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'");
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, min($pages, (int) input('page', 1)));
        $rows = db()->all(
            "SELECT u.id, u.username, u.avatar, u.created_at, u.last_seen, r.slug AS role_slug, r.name AS role_name
             FROM {users} u JOIN {roles} r ON r.id = u.role_id WHERE u.status = 'active'
             ORDER BY r.level DESC, u.username LIMIT $per OFFSET " . (($page - 1) * $per)
        );
        return View::page(t('Members'), render('members/index', ['rows' => $rows, 'page' => $page, 'pages' => $pages, 'total' => $total]));
    }

    public function show(array $p): string
    {
        $u = db()->one(
            "SELECT u.id, u.username, u.avatar, u.bio, u.signature, u.created_at, u.last_seen, r.slug AS role_slug, r.name AS role_name
             FROM {users} u JOIN {roles} r ON r.id = u.role_id WHERE u.username = ? AND u.status = 'active'",
            [$p['name']]
        );
        if (!$u) {
            (new \MagicCMS\Core\Router())->notFound();
            return '';
        }
        return View::page($u['username'], render('members/show', ['u' => $u]));
    }
}
