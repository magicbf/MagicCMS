<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Matches;

use MagicCMS\Core\{Router, View};

final class Controller
{
    /** "All matches": results with filters, upcoming fixtures, record. */
    public function index(): string
    {
        $result = in_array(input('result'), ['win', 'loss', 'draw'], true) ? (string) input('result') : '';
        $game = mb_substr((string) input('game', ''), 0, 60);
        $where = ["status = 'finished'"];
        $args = [];
        if ($result === 'win') { $where[] = 'score_us > score_them'; }
        if ($result === 'loss') { $where[] = 'score_us < score_them'; }
        if ($result === 'draw') { $where[] = 'score_us = score_them'; }
        if ($game !== '') { $where[] = 'game = ?'; $args[] = $game; }
        $w = implode(' AND ', $where);
        $total = (int) db()->val("SELECT COUNT(*) FROM {matches} WHERE $w", $args);
        $pages = max(1, (int) ceil($total / Matches::PER_PAGE));
        $page = max(1, min($pages, (int) input('page', 1)));
        $rows = db()->all("SELECT * FROM {matches} WHERE $w ORDER BY played_at DESC, id DESC LIMIT " . Matches::PER_PAGE . ' OFFSET ' . (($page - 1) * Matches::PER_PAGE), $args);
        $upcoming = db()->all("SELECT * FROM {matches} WHERE status = 'upcoming' ORDER BY played_at ASC LIMIT 5");
        $games = array_column(db()->all("SELECT DISTINCT game FROM {matches} WHERE game <> '' ORDER BY game"), 'game');
        $q = http_build_query(array_filter(['result' => $result, 'game' => $game]));
        return View::page(t('Matches'), render('matches/index', [
            'rows' => $rows, 'upcoming' => $upcoming, 'stats' => Matches::stats(), 'games' => $games, 'result' => $result, 'game' => $game,
            'page' => $page, 'pages' => $pages, 'base' => url('matches') . ($q ? '?' . $q : ''),
        ]), ['class' => 'page-matches']);
    }

    public function show(array $p): string
    {
        $m = db()->one('SELECT * FROM {matches} WHERE id = ?', [(int) $p['id']]);
        if (!$m) {
            (new Router())->notFound();
            return '';
        }
        return View::page('vs ' . $m['opponent'], render('matches/show', ['m' => $m]), ['class' => 'page-matches']);
    }
}
