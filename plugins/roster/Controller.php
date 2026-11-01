<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Roster;

use MagicCMS\Core\{Router, View};

final class Controller
{
    public function index(): string
    {
        $squad = mb_substr((string) input('squad', ''), 0, 60);
        $squads = array_column(db()->all("SELECT squad, COUNT(*) n FROM {roster_members} WHERE is_active = 1 AND squad <> '' GROUP BY squad ORDER BY MIN(sort_order)"), 'squad');
        $w = 'is_active = 1' . ($squad !== '' ? ' AND squad = ?' : '');
        $rows = array_map([Roster::class, 'decode'], db()->all("SELECT * FROM {roster_members} WHERE $w ORDER BY squad, sort_order, nickname", $squad !== '' ? [$squad] : []));
        $groups = [];
        foreach ($rows as $m) {
            $groups[$m['squad'] !== '' ? $m['squad'] : t('Team')][] = $m;
        }
        $former = $squad === '' ? array_map([Roster::class, 'decode'], db()->all('SELECT * FROM {roster_members} WHERE is_active = 0 ORDER BY nickname')) : [];
        return View::page(t('Roster'), render('roster/index', ['groups' => $groups, 'squads' => $squads, 'squad' => $squad, 'former' => $former, 'count' => count($rows)]), ['class' => 'page-roster']);
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
