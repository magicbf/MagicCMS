<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Admin;

use MagicCMS\Core\{DisplayRoles, Settings, View};

/** Admin → Roles: cosmetic display roles, their colours/styles, where they show up, and who has them. */
final class RolesController
{
    private function back(): never
    {
        redirect('/admin/roles');
    }

    public function index(): string
    {
        $roles = db()->all('SELECT * FROM {display_roles} ORDER BY sort_order, id');
        $members = [];
        foreach (db()->all('SELECT x.role_id, u.id, u.username FROM {user_display_roles} x JOIN {users} u ON u.id = x.user_id ORDER BY u.username') as $m) {
            $members[(int) $m['role_id']][] = $m;
        }
        $title = t('Roles');
        return View::page($title, render('admin/_wrap', ['tab' => 'roles', 'title' => $title, 'inner' => render('admin/roles', ['roles' => $roles, 'members' => $members])]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function save(): void
    {
        $id = (int) input('id', 0);
        $name = trim(mb_substr((string) input('name'), 0, 40));
        if ($name === '') {
            flash('danger', t('Please give the role a name.'));
            $this->back();
        }
        $color = (string) input('color');
        $color = is_hex($color) ? strtolower($color) : '#7ae24b';
        $style = array_key_exists((string) input('style'), DisplayRoles::STYLES) ? (string) input('style') : 'soft';
        $icon = mb_substr(trim((string) input('icon')), 0, 8);
        $places = array_values(array_intersect(array_keys(DisplayRoles::PLACES), array_map('strval', (array) ($_POST['places'] ?? []))));
        $order = max(0, min(9999, (int) input('sort_order', 0)));
        $on = empty($_POST['enabled']) ? 0 : 1;
        if ($id && db()->val('SELECT id FROM {display_roles} WHERE id = ?', [$id])) {
            db()->exec('UPDATE {display_roles} SET name=?, color=?, icon=?, style=?, places=?, sort_order=?, enabled=? WHERE id=?', [$name, $color, $icon, $style, implode(',', $places), $order, $on, $id]);
            audit('roles.save', $name);
            flash('success', t('Role saved.'));
        } else {
            $order = $order ?: ((int) db()->val('SELECT COALESCE(MAX(sort_order), 0) FROM {display_roles}') + 10);
            db()->exec('INSERT INTO {display_roles} (name, color, icon, style, places, sort_order, enabled, created_at) VALUES (?,?,?,?,?,?,?,?)', [$name, $color, $icon, $style, implode(',', $places), $order, $on, now()]);
            audit('roles.create', $name);
            flash('success', t('Role created. Now assign it to members below.'));
        }
        $this->back();
    }

    public function delete(array $p): void
    {
        $r = db()->one('SELECT name FROM {display_roles} WHERE id = ?', [(int) $p['id']]);
        if ($r) {
            db()->exec('DELETE FROM {display_roles} WHERE id = ?', [(int) $p['id']]);
            audit('roles.delete', $r['name']);
            flash('success', t('Role deleted.'));
        }
        $this->back();
    }

    public function assign(array $p): void
    {
        $id = (int) $p['id'];
        if (!db()->val('SELECT id FROM {display_roles} WHERE id = ?', [$id])) {
            $this->back();
        }
        $names = array_slice(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) input('usernames', '')) ?: [])), 0, 25);
        $added = 0;
        $missing = [];
        foreach ($names as $n) {
            $uid = (int) db()->val('SELECT id FROM {users} WHERE username = ?', [ltrim($n, '@')]);
            if (!$uid) { $missing[] = $n; continue; }
            db()->exec('INSERT IGNORE INTO {user_display_roles} (user_id, role_id) VALUES (?,?)', [$uid, $id]);
            $added++;
        }
        if ($added) { audit('roles.assign', '#' . $id . ' ×' . $added); flash('success', t('{n} member(s) added.', ['n' => $added])); }
        if ($missing) { flash('warning', t('No such member: {names}', ['names' => implode(', ', $missing)])); }
        $this->back();
    }

    public function unassign(array $p): void
    {
        db()->exec('DELETE FROM {user_display_roles} WHERE role_id = ? AND user_id = ?', [(int) $p['id'], (int) input('uid', 0)]);
        audit('roles.unassign', '#' . (int) $p['id']);
        $this->back();
    }

    public function settings(): void
    {
        Settings::set('roles.max_shown', max(1, min(5, (int) input('max_shown', 3))));
        flash('success', t('Settings saved.'));
        $this->back();
    }
}
