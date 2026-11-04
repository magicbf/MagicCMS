<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Roster;

use MagicCMS\Core\{Router, View};

final class Controller
{
    public function index(): string
    {
        $rows = array_map([Roster::class, 'decode'], db()->all('SELECT * FROM {roster_members} WHERE is_active = 1'));
        $former = array_map([Roster::class, 'decode'], db()->all('SELECT * FROM {roster_members} WHERE is_active = 0 ORDER BY nickname'));
        return View::page(t('Roster'), render('roster/index', ['groups' => Roster::groupByRole($rows), 'former' => $former, 'count' => count($rows)]), ['class' => 'page-roster']);
    }

    public function show(array $p): string
    {
        $m = db()->one('SELECT * FROM {roster_members} WHERE id = ?', [(int) $p['id']]);
        if (!$m) {
            (new Router())->notFound();
            return '';
        }
        $m = Roster::decode($m);
        $u = $m['user_id'] ? db()->one("SELECT id, username FROM {users} WHERE id = ? AND status = 'active'", [$m['user_id']]) : null;
        return View::page($m['nickname'], render('roster/show', ['m' => $m, 'u' => $u]), ['class' => 'page-roster']);
    }
}
