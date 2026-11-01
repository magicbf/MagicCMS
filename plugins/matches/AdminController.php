<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Matches;

use MagicCMS\Core\{Nav, Sanitizer, Settings, View};

/** Admin → Matches. */
final class AdminController
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'matches', 'title' => $title, 'inner' => render('matches/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function index(): string
    {
        $st = in_array(input('status'), ['upcoming', 'finished'], true) ? (string) input('status') : '';
        $per = 25;
        $w = $st ? 'WHERE status = ?' : '';
        $a = $st ? [$st] : [];
        $total = (int) db()->val("SELECT COUNT(*) FROM {matches} $w", $a);
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, min($pages, (int) input('page', 1)));
        $rows = db()->all("SELECT * FROM {matches} $w ORDER BY played_at DESC, id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $a);
        return $this->page(t('Matches'), 'admin_list', ['rows' => $rows, 'status' => $st, 'page' => $page, 'pages' => $pages, 'count' => Matches::panelCount()]);
    }

    public function saveSettings(): never
    {
        $n = (int) input('panel_count');
        Settings::set('matches.panel_count', in_array($n, Matches::PANEL_OPTIONS, true) ? $n : 5);
        audit('matches.settings', 'panel_count=' . setting('matches.panel_count'));
        flash('success', t('Recent Matches panel will show the last {n} matches.', ['n' => setting('matches.panel_count')]));
        redirect('/admin/matches');
    }

    public function edit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT * FROM {matches} WHERE id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            redirect('/admin/matches');
        }
        return $this->page($row ? t('Edit match') : t('New match'), 'admin_edit', ['row' => $row, 'games' => array_column(db()->all("SELECT DISTINCT game FROM {matches} WHERE game <> '' ORDER BY game"), 'game')]);
    }

    public function save(): never
    {
        $id = (int) input('id', 0);
        $back = $id ? '/admin/matches/' . $id : '/admin/matches/new';
        $opp = mb_substr((string) input('opponent'), 0, 80);
        $status = input('status') === 'finished' ? 'finished' : 'upcoming';
        $at = local_to_utc((string) input('played_at'));
        $us = input('score_us') !== '' ? (int) input('score_us') : null;
        $them = input('score_them') !== '' ? (int) input('score_them') : null;
        $vod = (string) input('vod_url');
        if ($opp === '' || !$at) {
            flash('danger', t('Opponent and date are required.'));
            redirect($back);
        }
        if ($status === 'finished' && ($us === null || $them === null || $us < 0 || $them < 0 || $us > 999 || $them > 999)) {
            flash('danger', t('A finished match needs both scores (0–999).'));
            redirect($back);
        }
        if ($vod !== '' && !preg_match('#^https?://#i', (string) Nav::safeUrl($vod))) {
            flash('danger', t('The VOD link must start with http:// or https://'));
            redirect($back);
        }
        if ($status === 'upcoming') {
            $us = $them = null;
        }
        $f = [$opp, mb_substr((string) input('opponent_tag'), 0, 12), mb_substr((string) input('game'), 0, 60), mb_substr((string) input('competition'), 0, 120), mb_substr((string) input('map'), 0, 80), $status, $us, $them, $at, mb_substr($vod, 0, 300), Sanitizer::html((string) ($_POST['report'] ?? '')) ?: null];
        if ($id && db()->val('SELECT id FROM {matches} WHERE id = ?', [$id])) {
            db()->exec('UPDATE {matches} SET opponent=?, opponent_tag=?, game=?, competition=?, map=?, status=?, score_us=?, score_them=?, played_at=?, vod_url=?, report=?, updated_at=? WHERE id=?', [...$f, now(), $id]);
        } else {
            $id = db()->insert('INSERT INTO {matches} (opponent, opponent_tag, game, competition, map, status, score_us, score_them, played_at, vod_url, report, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$f, user()['id'], now(), now()]);
        }
        audit('matches.save', 'vs ' . $opp);
        flash('success', t('Match saved.'));
        redirect('/admin/matches');
    }

    public function delete(array $p): never
    {
        db()->exec('DELETE FROM {matches} WHERE id = ?', [(int) $p['id']]);
        audit('matches.delete', '#' . (int) $p['id']);
        flash('success', t('Match deleted.'));
        redirect('/admin/matches');
    }
}
