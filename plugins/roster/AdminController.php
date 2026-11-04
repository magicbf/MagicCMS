<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Roster;

use MagicCMS\Core\{Uploads, View};

final class AdminController
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'roster', 'title' => $title, 'inner' => render('roster/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function index(): string
    {
        $all = db()->all('SELECT * FROM {roster_members}');
        $active = Roster::sort(array_values(array_filter($all, static fn($m) => (int) $m['is_active'] === 1)));
        $former = Roster::sort(array_values(array_filter($all, static fn($m) => (int) $m['is_active'] !== 1)));
        return $this->page(t('Roster'), 'admin_list', ['rows' => [...$active, ...$former], 'tab' => 'members']);
    }

    public function edit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT * FROM {roster_members} WHERE id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            redirect('/admin/roster');
        }
        return $this->page($row ? t('Edit member') : t('New member'), 'admin_edit', [
            'row' => $row ? Roster::decode($row) : null,
            'roles' => Roster::roles(),
            'linked' => !$row ? mb_substr((string) input('username', ''), 0, 32) : ($row['user_id'] ? (string) db()->val('SELECT username FROM {users} WHERE id = ?', [$row['user_id']]) : ''),
        ]);
    }

    public function save(): never
    {
        $id = (int) input('id', 0);
        $old = $id ? db()->one('SELECT * FROM {roster_members} WHERE id = ?', [$id]) : null;
        $back = $id ? '/admin/roster/' . $id : '/admin/roster/new';
        $nick = mb_substr((string) input('nickname'), 0, 40);
        if ($nick === '') {
            flash('danger', t('A nickname is required.'));
            redirect($back);
        }
        $uname = (string) input('username');
        $uid = null;
        if ($uname !== '') {
            $uid = db()->val("SELECT id FROM {users} WHERE username = ? AND status = 'active'", [$uname]);
            if (!$uid) {
                flash('danger', t('No active member with the username “{name}”.', ['name' => $uname]));
                redirect($back);
            }
        }
        $cc = strtoupper((string) input('country'));
        $cc = preg_match('/^[A-Z]{2}$/', $cc) ? $cc : '';
        $joined = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) input('joined_on')) ? (string) input('joined_on') : null;
        $photo = $old['photo'] ?? null;
        try {
            if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $new = Uploads::picture($_FILES['photo'], 'roster', 'roster', 640, 800, 4 / 5);
                Uploads::deletePicture('roster', $photo);
                $photo = $new;
            } elseif (!empty($_POST['remove_photo'])) {
                Uploads::deletePicture('roster', $photo);
                $photo = null;
            }
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect($back);
        }
        $f = [
            $uid ?: null, $nick, mb_substr((string) input('real_name'), 0, 80), mb_substr(trim((string) input('role')), 0, 60), mb_substr(trim((string) input('position')), 0, 40), $cc,
            mb_substr(trim((string) ($_POST['bio'] ?? '')), 0, 1500), $photo, $joined, empty($_POST['is_active']) ? 0 : 1, (int) input('sort_order'),
            json_encode(Roster::cleanSocials((array) ($_POST['social'] ?? [])), JSON_UNESCAPED_SLASHES),
            json_encode(Roster::cleanStats((array) ($_POST['stat_label'] ?? []), (array) ($_POST['stat_value'] ?? [])), JSON_UNESCAPED_UNICODE),
        ];
        $cols = 'user_id=?, nickname=?, real_name=?, role=?, position=?, country=?, bio=?, photo=?, joined_on=?, is_active=?, sort_order=?, socials=?, stats=?';
        if ($old) {
            db()->exec("UPDATE {roster_members} SET $cols, updated_at=? WHERE id=?", [...$f, now(), $id]);
        } else {
            $id = db()->insert('INSERT INTO {roster_members} (user_id, nickname, real_name, role, position, country, bio, photo, joined_on, is_active, sort_order, socials, stats, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$f, now(), now()]);
        }
        audit('roster.save', $nick);
        flash('success', t('Member saved.'));
        redirect('/admin/roster');
    }

    public function delete(array $p): never
    {
        $m = db()->one('SELECT id, photo, nickname FROM {roster_members} WHERE id = ?', [(int) $p['id']]);
        if ($m) {
            Uploads::deletePicture('roster', $m['photo']);
            db()->exec('DELETE FROM {roster_members} WHERE id = ?', [$m['id']]);
            audit('roster.delete', $m['nickname']);
            flash('success', t('Member deleted.'));
        }
        redirect('/admin/roster');
    }

    public function toggle(array $p): never
    {
        db()->exec('UPDATE {roster_members} SET is_active = 1 - is_active WHERE id = ?', [(int) $p['id']]);
        redirect('/admin/roster');
    }

    public function move(array $p): never
    {
        $m = db()->one('SELECT role FROM {roster_members} WHERE id = ?', [(int) $p['id']]);
        if ($m) {   // members move up/down inside their own role
            $ids = array_map('intval', array_column(db()->all('SELECT id FROM {roster_members} WHERE LOWER(role) = LOWER(?) ORDER BY sort_order, nickname', [$m['role']]), 'id'));
            $pos = array_search((int) $p['id'], $ids, true);
            $swap = $p['dir'] === 'up' ? $pos - 1 : $pos + 1;
            if ($pos !== false && isset($ids[$swap])) {
                [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            }
            foreach ($ids as $n => $id) {
                db()->exec('UPDATE {roster_members} SET sort_order = ? WHERE id = ?', [$n + 1, $id]);
            }
        }
        redirect('/admin/roster');
    }

    /** The order in which roles are listed on the roster page and in the panel. */
    public function roleOrder(): never
    {
        $names = [];
        foreach (explode(',', (string) input('role_order', '')) as $n) {
            $n = trim(mb_substr($n, 0, 60));
            if ($n !== '' && !in_array(mb_strtolower($n), array_map('mb_strtolower', $names), true) && count($names) < 40) {
                $names[] = $n;
            }
        }
        \MagicCMS\Core\Settings::set('roster.role_order', implode(', ', $names));
        audit('roster.role_order');
        flash('success', t('Role order saved.'));
        redirect('/admin/roster');
    }

    /** Everything about how the roster is shown, in one place: the panel, the role order and the role colours. */
    public function settings(): string
    {
        $cfg = Roster::panelConfig();
        $order = Roster::roleOrder();
        $used = Roster::roles();
        $names = [];
        foreach ($used as $r) { $names[mb_strtolower($r)] = $r; }
        $list = [];
        foreach (array_merge($order, array_keys($names)) as $k) {   // listed roles first (in that order), then roles in use
            $k = mb_strtolower($k);
            if (!isset($list[$k])) {
                $list[$k] = $names[$k] ?? ucfirst($k);
            }
        }
        // keep the display spelling of listed roles from the stored order
        foreach (array_filter(array_map('trim', explode(',', (string) setting('roster.role_order', '')))) as $n) {
            $list[mb_strtolower($n)] = $names[mb_strtolower($n)] ?? $n;
        }
        $counts = [];
        foreach (db()->all('SELECT LOWER(role) r, COUNT(*) n FROM {roster_members} GROUP BY LOWER(role)') as $c) { $counts[$c['r']] = (int) $c['n']; }
        return $this->page(t('Roster – panel & roles'), 'admin_settings', [
            'cfg' => $cfg, 'roles' => $list, 'counts' => $counts, 'colors' => Roster::roleColors(),
            'vis' => $cfg['row'] ? (json_decode((string) $cfg['row']['visibility'], true) ?: []) : [],
        ]);
    }

    public function settingsSave(): never
    {
        // 1. role order + colours
        $names = [];
        $colors = [];
        $byIdx = [];
        foreach ((array) ($_POST['roles'] ?? []) as $i => $n) {
            $n = trim(mb_substr(is_string($n) ? $n : '', 0, 60));
            if ($n !== '' && !str_contains($n, ',') && !isset($names[mb_strtolower($n)]) && count($names) < 40) {
                $names[mb_strtolower($n)] = $n;
                $byIdx[(string) $i] = mb_strtolower($n);
            }
        }
        $add = trim(mb_substr(str_replace(',', ' ', (string) input('new_role', '')), 0, 60));
        if ($add !== '' && !isset($names[mb_strtolower($add)]) && count($names) < 40) {
            $names[mb_strtolower($add)] = $add;
        }
        foreach ((array) ($_POST['role_color'] ?? []) as $i => $v) {
            if (is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v) && !empty($_POST['role_color_on'][$i]) && isset($byIdx[(string) $i])) {
                $colors[$byIdx[(string) $i]] = strtolower($v);
            }
        }
        \MagicCMS\Core\Settings::set('roster.role_order', implode(', ', $names));
        \MagicCMS\Core\Settings::set('roster.role_colors', $colors);
        // 2. the panel: options, title, place and who sees it
        $cfg = Roster::panelConfig();
        if ($cfg['row']) {
            $vals = [];
            foreach ($cfg['schema'] as $name => $def) {
                $raw = $_POST['s'][$name] ?? ($def['default'] ?? '');
                $raw = is_string($raw) ? $raw : '';
                $vals[$name] = match ($def['type'] ?? 'text') {
                    'number' => max(0, min(200, (int) $raw)),
                    'checkbox' => !empty($_POST['s'][$name]),
                    'select' => array_key_exists($raw, (array) ($def['options'] ?? [])) ? $raw : (string) ($def['default'] ?? ''),
                    default => mb_substr(trim($raw), 0, 255),
                };
            }
            $who = (string) input('who', 'all');
            $vis = json_encode(['who' => in_array($who, ['all', 'guests', 'members', 'staff'], true) ? $who : 'all', 'desktop' => !empty($_POST['desktop']), 'tablet' => !empty($_POST['tablet']), 'mobile' => !empty($_POST['mobile'])]);
            $pos = (string) input('position', 'right');
            db()->exec('UPDATE {panels} SET settings = ?, title = ?, position = ?, enabled = ?, visibility = ? WHERE id = ?', [
                json_encode($vals, JSON_UNESCAPED_UNICODE), mb_substr((string) input('title'), 0, 120) ?: 'Our Team',
                in_array($pos, ['left', 'right', 'above_footer', 'footer'], true) ? $pos : 'right', empty($_POST['enabled']) ? 0 : 1, $vis, $cfg['row']['id'],
            ]);
        }
        audit('roster.settings');
        flash('success', t('Roster settings saved.'));
        redirect('/admin/roster/settings');
    }
}
