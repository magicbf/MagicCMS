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
        return $this->page(t('Roster'), 'admin_list', ['rows' => [...$active, ...$former], 'order' => (string) setting('roster.role_order', ''), 'roles' => Roster::roles()]);
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
}
