<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Admin;

use MagicCMS\Core\{Appearance, Auth, Layout, Mailer, Nav, Panels, Sanitizer, Settings, Uploads, View};

final class Controller
{
    private function page(string $title, string $tab, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => $tab, 'title' => $title, 'inner' => render('admin/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    private function back(string $to): never
    {
        redirect($to);
    }

    private static function pick(string $v, array $allowed, string $default): string
    {
        return in_array($v, $allowed, true) ? $v : $default;
    }

    // ---------------------------------------------------------------- dashboard
    public function dashboard(): string
    {
        $stats = [
            t('Members') => (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'"),
            t('Pending approval') => (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'pending'"),
            t('Pages') => (int) db()->val('SELECT COUNT(*) FROM {pages}'),
            t('Shouts') => (int) db()->val('SELECT COUNT(*) FROM {chat_messages}'),
        ];
        $log = db()->all('SELECT a.*, u.username FROM {audit_log} a LEFT JOIN {users} u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 10');
        $sys = [
            'MagicCMS' => MCMS_VERSION, 'PHP' => PHP_VERSION, 'MySQL' => (string) db()->val('SELECT VERSION()'),
            'GD / WebP' => (extension_loaded('gd') ? 'yes' : 'NO') . ' / ' . (function_exists('imagewebp') ? 'yes' : 'no (JPEG fallback)'),
            'Password hash' => defined('PASSWORD_ARGON2ID') ? 'Argon2id' : 'bcrypt',
        ];
        return $this->page(t('Dashboard'), 'dashboard', 'dashboard', [
            'stats' => $stats, 'log' => $log, 'sys' => $sys, 'setupLeft' => is_file(MCMS_ROOT . '/setup.php'),
        ]);
    }

    // ---------------------------------------------------------------- settings
    public function settings(): string
    {
        return $this->page(t('Settings'), 'settings', 'settings');
    }

    public function settingsSave(): void
    {
        $name = mb_substr((string) input('site_name'), 0, 60) ?: 'MagicCMS';
        Settings::set('site.name', $name);
        Settings::set('site.tagline', mb_substr((string) input('site_tagline'), 0, 120));
        Settings::set('site.description', mb_substr((string) input('site_description'), 0, 300));
        Settings::set('site.footer', mb_substr((string) input('site_footer'), 0, 200));
        $tz = (string) input('timezone');
        if (in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
            Settings::set('site.timezone', $tz);
        }
        Settings::set('layout.mode', self::pick((string) input('layout_mode'), Layout::MODES, 'both'));
        Settings::set('layout.header', self::pick((string) input('layout_header'), array_keys(Layout::HEADERS), 'classic'));
        Settings::set('layout.rhythm', self::pick((string) input('layout_rhythm'), array_keys(Layout::RHYTHMS), 'even'));
        Settings::set('layout.mirror', !empty($_POST['layout_mirror']));
        Settings::set('auth.registration', self::pick((string) input('registration'), ['open', 'approval', 'closed'], 'open'));
        Settings::set('upload.avatar_max_mb', max(1, min(5, (int) input('avatar_mb', 5))));
        Settings::set('online.window_min', max(1, min(60, (int) input('online_min', 5))));
        Settings::set('auth.email_verify', !empty($_POST['email_verify']));
        Settings::set('site.maintenance', !empty($_POST['maintenance']));
        Settings::set('site.logo_show_name', !empty($_POST['logo_show_name']));
        audit('settings.save');
        flash('success', t('Settings saved.'));
        $this->back('/admin/settings');
    }

    public function mailSave(): void
    {
        Settings::set('mail.driver', self::pick((string) input('driver'), ['mail', 'smtp', 'log'], 'mail'));
        Settings::set('mail.from_name', mb_substr((string) input('from_name'), 0, 80));
        $fe = (string) input('from_email');
        Settings::set('mail.from_email', filter_var($fe, FILTER_VALIDATE_EMAIL) ? $fe : '');
        Settings::set('mail.smtp_host', mb_substr((string) input('smtp_host'), 0, 190));
        Settings::set('mail.smtp_port', max(1, min(65535, (int) input('smtp_port', 587))));
        Settings::set('mail.smtp_secure', self::pick((string) input('smtp_secure'), ['none', 'tls', 'ssl'], 'tls'));
        Settings::set('mail.smtp_user', mb_substr((string) input('smtp_user'), 0, 190));
        if ((string) ($_POST['smtp_pass'] ?? '') !== '') { // blank = keep the stored (encrypted) password
            Settings::set('mail.smtp_pass', Mailer::encrypt((string) $_POST['smtp_pass']));
        } elseif (!empty($_POST['smtp_pass_clear'])) {
            Settings::set('mail.smtp_pass', '');
        }
        audit('mail.settings', (string) setting('mail.driver'));
        flash('success', t('E-mail settings saved.'));
        $this->back('/admin/settings');
    }

    public function mailTest(): void
    {
        $to = (string) user()['email'];
        $ok = Mailer::send($to, t('MagicCMS test e-mail'), Mailer::template(t('It works!'), '<p>' . e(t('This is a test message from your MagicCMS installation.')) . '</p>'));
        flash($ok ? 'success' : 'danger', $ok ? t('Test e-mail sent to {to} (driver: {d}).', ['to' => $to, 'd' => (string) setting('mail.driver')]) : t('Sending failed: {e}', ['e' => Mailer::$error]));
        $this->back('/admin/settings');
    }

    public function userVerify(array $p): void
    {
        $t = db()->one('SELECT u.id, u.username, u.role_id, r.level AS role_level FROM {users} u JOIN {roles} r ON r.id = u.role_id WHERE u.id = ?', [(int) $p['id']]);
        if ($t && Auth::canManage($t)) {
            db()->exec('UPDATE {users} SET email_verified_at = COALESCE(email_verified_at, ?) WHERE id = ?', [now(), $t['id']]);
            audit('user.verify', $t['username']);
            flash('success', t('E-mail marked as verified.'));
        }
        $this->back('/admin/users');
    }

    public function logoUpload(): void
    {
        try {
            $name = Uploads::logo($_FILES['logo'] ?? []);
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            $this->back('/admin/settings');
        }
        Uploads::deleteLogo((string) setting('site.logo', ''));
        Settings::set('site.logo', $name);
        audit('logo.upload', $name);
        flash('success', t('Logo updated.'));
        $this->back('/admin/settings');
    }

    public function logoReset(): void
    {
        Uploads::deleteLogo((string) setting('site.logo', ''));
        Settings::set('site.logo', '');
        audit('logo.reset');
        flash('success', t('Default logo restored.'));
        $this->back('/admin/settings');
    }

    // ---------------------------------------------------------------- appearance
    public function appearance(): string
    {
        $c = Appearance::colors();
        $warn = [];
        if (Appearance::contrast($c['primary'], $c['on_primary']) < 4.5) {
            $warn[] = t('Text on primary buttons has low contrast ({r}:1, WCAG AA needs 4.5:1).', ['r' => number_format(Appearance::contrast($c['primary'], $c['on_primary']), 2)]);
        }
        return $this->page(t('Appearance'), 'appearance', 'appearance', ['colors' => $c, 'warn' => $warn, 'themes' => View::themes()]);
    }

    public function appearanceSave(): void
    {
        $scheme = (string) input('scheme');
        Settings::set('appearance.scheme', $scheme === 'custom' ? 'custom' : self::pick($scheme, array_keys(Appearance::PRESETS), 'crimson'));
        $colors = [];
        foreach (array_keys(Appearance::TOKENS) as $k) {
            $v = (string) input('c_' . $k);
            if (is_hex($v)) {
                $colors[$k] = strtolower($v);
            }
        }
        Settings::set('appearance.colors', $colors ?: new \stdClass());
        Settings::set('appearance.mode_default', self::pick((string) input('mode_default'), ['dark', 'light', 'auto'], 'dark'));
        Settings::set('appearance.allow_user_mode', !empty($_POST['allow_user_mode']));
        Settings::set('appearance.radius', max(0, min(32, (int) input('radius', 16))));
        Settings::set('appearance.motion', self::pick((string) input('motion'), ['off', 'subtle', 'full'], 'full'));
        Settings::set('nav.button_style', self::pick((string) input('nav_style'), ['gradient', 'flash', 'solid', 'outline', 'glass'], 'gradient'));
        Settings::set('nav.hover_fx', self::pick((string) input('nav_fx'), ['shine', 'glow', 'lift', 'none'], 'shine'));
        $theme = (string) input('theme');
        if (isset(View::themes()[$theme])) {
            $changed = $theme !== (string) setting('theme.active', 'magic-red');
            Settings::set('theme.active', $theme);
            if ($changed && ($applied = Appearance::applyThemeDefaults(View::themes()[$theme]))) {
                flash('info', t('This theme suggests: {what}. Applied – adjust it any time below.', ['what' => implode(', ', $applied)]));
            }
        }
        audit('appearance.save');
        flash('success', t('Appearance saved.'));
        $this->back('/admin/appearance');
    }

    // ---------------------------------------------------------------- users
    public function users(): string
    {
        $q = (string) input('q', '');
        $per = 25;
        $where = $q !== '' ? 'WHERE u.username LIKE ? OR u.email LIKE ?' : '';
        $args = $q !== '' ? ['%' . addcslashes($q, '%_\\') . '%', '%' . addcslashes($q, '%_\\') . '%'] : [];
        $total = (int) db()->val("SELECT COUNT(*) FROM {users} u $where", $args);
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, min($pages, (int) input('page', 1)));
        $rows = db()->all(
            "SELECT u.id, u.username, u.email, u.status, u.email_verified_at, u.created_at, u.last_login, u.role_id, r.slug AS role_slug, r.name AS role_name, r.level AS role_level
             FROM {users} u JOIN {roles} r ON r.id = u.role_id $where ORDER BY r.level DESC, u.username LIMIT $per OFFSET " . (($page - 1) * $per),
            $args
        );
        return $this->page(t('Users'), 'users', 'users', [
            'rows' => $rows, 'roles' => Auth::assignableRoles(), 'q' => $q, 'page' => $page, 'pages' => $pages, 'total' => $total,
        ]);
    }

    public function userSave(array $p): void
    {
        $t = db()->one('SELECT u.id, u.username, u.role_id, r.level AS role_level FROM {users} u JOIN {roles} r ON r.id = u.role_id WHERE u.id = ?', [(int) $p['id']]);
        if (!$t || !Auth::canManage($t)) {
            flash('danger', t('You cannot modify this user.'));
            $this->back('/admin/users');
        }
        $role = db()->one('SELECT id, slug FROM {roles} WHERE id = ?', [(int) input('role_id')]);
        $allowed = array_column(Auth::assignableRoles(), 'id');
        if (!$role || !in_array((int) $role['id'], array_map('intval', $allowed), true)) {
            flash('danger', t('You cannot assign that role.'));
            $this->back('/admin/users');
        }
        $status = self::pick((string) input('status'), ['active', 'pending', 'banned'], 'active');
        db()->exec('UPDATE {users} SET role_id = ?, status = ? WHERE id = ?', [$role['id'], $status, $t['id']]);
        if ($status !== 'active') {
            db()->exec('DELETE FROM {remember_tokens} WHERE user_id = ?', [$t['id']]);
        }
        audit('user.update', "{$t['username']} → role={$role['slug']}, status=$status");
        flash('success', t('User updated.'));
        $this->back('/admin/users');
    }

    // ---------------------------------------------------------------- panels
    public function panels(): string
    {
        $rows = db()->all('SELECT * FROM {panels} ORDER BY position, sort_order, id');
        return $this->page(t('Panels'), 'panels', 'panels', ['rows' => $rows, 'registry' => Panels::registry()]);
    }

    private static function visibilityJson(): string
    {
        return json_encode(['who' => self::pick((string) input('who'), ['all', 'guests', 'members', 'staff'], 'all'), 'mobile' => !empty($_POST['mobile'])]);
    }

    public function panelAdd(): void
    {
        $key = (string) input('panel_key');
        $reg = Panels::registry()[$key] ?? null;
        if (!$reg) {
            flash('danger', t('Unknown panel.'));
            $this->back('/admin/panels');
        }
        if (empty($reg['meta']['multiple']) && db()->val('SELECT id FROM {panels} WHERE panel_key = ?', [$key])) {
            flash('danger', t('This panel can only be added once.'));
            $this->back('/admin/panels');
        }
        $pos = self::pick((string) input('position'), ['left', 'right'], 'left');
        $ord = (int) db()->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {panels} WHERE position = ?', [$pos]);
        db()->exec(
            'INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES (?,?,?,?,1,?)',
            [$key, mb_substr((string) ($reg['meta']['title'] ?? $key), 0, 120), $pos, $ord, json_encode(['who' => 'all', 'mobile' => true])]
        );
        audit('panel.add', $key);
        flash('success', t('Panel added.'));
        $this->back('/admin/panels');
    }

    public function panelSave(array $p): void
    {
        db()->exec(
            'UPDATE {panels} SET title = ?, position = ?, sort_order = ?, enabled = ?, visibility = ? WHERE id = ?',
            [mb_substr((string) input('title'), 0, 120) ?: 'Panel', self::pick((string) input('position'), ['left', 'right'], 'left'), (int) input('sort_order'), empty($_POST['enabled']) ? 0 : 1, self::visibilityJson(), (int) $p['id']]
        );
        flash('success', t('Panel saved.'));
        $this->back('/admin/panels');
    }

    public function panelDelete(array $p): void
    {
        db()->exec('DELETE FROM {panels} WHERE id = ?', [(int) $p['id']]);
        audit('panel.delete', '#' . (int) $p['id']);
        flash('success', t('Panel removed.'));
        $this->back('/admin/panels');
    }

    public function panelEdit(array $p): string
    {
        $row = db()->one('SELECT * FROM {panels} WHERE id = ?', [(int) $p['id']]);
        if (!$row || !isset(Panels::registry()[$row['panel_key']])) {
            $this->back('/admin/panels');
        }
        $reg = Panels::registry()[$row['panel_key']];
        return $this->page(t('Panel settings'), 'panels', 'panel_edit', [
            'row' => $row, 'schema' => $reg['meta']['settings'] ?? [], 'values' => Panels::settings($row['panel_key'], $row['settings']),
        ]);
    }

    public function panelEditSave(array $p): void
    {
        $row = db()->one('SELECT * FROM {panels} WHERE id = ?', [(int) $p['id']]);
        $schema = $row ? (Panels::registry()[$row['panel_key']]['meta']['settings'] ?? []) : [];
        $vals = [];
        foreach ($schema as $name => $def) {
            $raw = $_POST['s'][$name] ?? ($def['default'] ?? '');
            $raw = is_string($raw) ? $raw : '';
            $vals[$name] = match ($def['type'] ?? 'text') {
                'number' => (int) $raw,
                'checkbox' => !empty($_POST['s'][$name]),
                'textarea' => $row['panel_key'] === 'custom_html' ? Sanitizer::html($raw) : mb_substr($raw, 0, 5000),
                default => mb_substr(trim($raw), 0, 255),
            };
        }
        if ($row) {
            db()->exec('UPDATE {panels} SET settings = ? WHERE id = ?', [json_encode($vals, JSON_UNESCAPED_UNICODE), $row['id']]);
            flash('success', t('Panel settings saved.'));
        }
        $this->back('/admin/panels');
    }

    // ---------------------------------------------------------------- navigation
    private function menuKey(): string
    {
        $k = (string) input('menu', 'main');
        return db()->val('SELECT menu_key FROM {menus} WHERE menu_key = ?', [$k]) ?: 'main';
    }

    public function nav(): string
    {
        $menu = db()->one('SELECT * FROM {menus} WHERE menu_key = ?', [$this->menuKey()]);
        $items = db()->all('SELECT * FROM {menu_items} WHERE menu_id = ? ORDER BY sort_order, id', [$menu['id']]);
        return $this->page(t('Navigation'), 'navigation', 'nav', [
            'menu' => $menu, 'menus' => db()->all('SELECT * FROM {menus} ORDER BY id'), 'items' => $items, 'item' => null,
            'tops' => array_values(array_filter($items, fn($i) => !$i['parent_id'])),
            'pages' => db()->all('SELECT id, title FROM {pages} ORDER BY title'), 'targets' => Nav::targets(),
        ]);
    }

    public function navEdit(array $p): string
    {
        $item = db()->one('SELECT * FROM {menu_items} WHERE id = ?', [(int) $p['id']]);
        if (!$item) {
            $this->back('/admin/navigation');
        }
        $menu = db()->one('SELECT * FROM {menus} WHERE id = ?', [$item['menu_id']]);
        $items = db()->all('SELECT * FROM {menu_items} WHERE menu_id = ? ORDER BY sort_order, id', [$menu['id']]);
        return $this->page(t('Edit menu item'), 'navigation', 'nav_edit', [
            'menu' => $menu, 'item' => $item,
            'tops' => array_values(array_filter($items, fn($i) => !$i['parent_id'] && (int) $i['id'] !== (int) $item['id'])),
            'pages' => db()->all('SELECT id, title FROM {pages} ORDER BY title'), 'targets' => Nav::targets(),
        ]);
    }

    public function navSave(): void
    {
        $menu = db()->one('SELECT * FROM {menus} WHERE menu_key = ?', [$this->menuKey()]);
        $id = (int) input('id', 0);
        $type = self::pick((string) input('target_type'), ['module', 'page', 'url', 'action'], 'url');
        $ref = match ($type) {
            'module' => self::pick((string) input('ref_module'), array_keys(Nav::targets()), 'home'),
            'page' => (string) (int) input('ref_page'),
            'action' => 'auth',
            default => (string) input('ref_url'),
        };
        $label = mb_substr((string) input('label'), 0, 80);
        if ($label === '' || ($type === 'url' && Nav::safeUrl($ref) === null) || ($type === 'page' && !db()->val('SELECT id FROM {pages} WHERE id = ?', [(int) $ref]))) {
            flash('danger', t('Please give the item a label and a valid target (http(s)://…, /path, mailto:… or #anchor).'));
            $this->back('/admin/navigation?menu=' . $menu['menu_key']);
        }
        $color = (string) input('color');
        $parent = (int) input('parent_id', 0);
        if ($parent && !db()->val('SELECT id FROM {menu_items} WHERE id = ? AND menu_id = ? AND parent_id = 0 AND id <> ?', [$parent, $menu['id'], $id])) {
            $parent = 0; // only one dropdown level; parent must be a top-level item of this menu
        }
        $vis = json_encode(['who' => self::pick((string) input('who'), ['all', 'guests', 'members', 'staff'], 'all'), 'mobile' => !empty($_POST['mobile'])]);
        $f = [$parent, $label, mb_substr((string) input('icon'), 0, 8), $type, mb_substr($ref, 0, 500), empty($_POST['new_tab']) ? 0 : 1, $vis, mb_substr((string) input('badge'), 0, 16), is_hex($color) ? $color : ''];
        if ($id && db()->val('SELECT id FROM {menu_items} WHERE id = ? AND menu_id = ?', [$id, $menu['id']])) {
            db()->exec('UPDATE {menu_items} SET parent_id=?, label=?, icon=?, target_type=?, target_ref=?, new_tab=?, visibility=?, badge=?, color=? WHERE id=?', [...$f, $id]);
        } else {
            $ord = (int) db()->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu['id']]);
            db()->exec('INSERT INTO {menu_items} (menu_id, parent_id, label, icon, target_type, target_ref, new_tab, visibility, badge, color, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)', [$menu['id'], ...$f, $ord]);
        }
        audit('nav.save', $label);
        flash('success', t('Menu item saved.'));
        $this->back('/admin/navigation?menu=' . $menu['menu_key']);
    }

    public function navDelete(array $p): void
    {
        $i = db()->one('SELECT i.id, m.menu_key FROM {menu_items} i JOIN {menus} m ON m.id = i.menu_id WHERE i.id = ?', [(int) $p['id']]);
        if ($i) {
            db()->exec('UPDATE {menu_items} SET parent_id = 0 WHERE parent_id = ?', [$i['id']]);
            db()->exec('DELETE FROM {menu_items} WHERE id = ?', [$i['id']]);
            audit('nav.delete', '#' . $i['id']);
            flash('success', t('Menu item deleted.'));
        }
        $this->back('/admin/navigation?menu=' . ($i['menu_key'] ?? 'main'));
    }

    public function navMove(array $p): void
    {
        $i = db()->one('SELECT i.*, m.menu_key FROM {menu_items} i JOIN {menus} m ON m.id = i.menu_id WHERE i.id = ?', [(int) $p['id']]);
        if ($i) {
            $all = db()->all('SELECT id FROM {menu_items} WHERE menu_id = ? AND parent_id = ? ORDER BY sort_order, id', [$i['menu_id'], $i['parent_id']]);
            $ids = array_map('intval', array_column($all, 'id'));
            $pos = array_search((int) $i['id'], $ids, true);
            $swap = $p['dir'] === 'up' ? $pos - 1 : $pos + 1;
            if ($pos !== false && isset($ids[$swap])) {
                [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            }
            foreach ($ids as $n => $id) { // renumber so ordering is always unambiguous
                db()->exec('UPDATE {menu_items} SET sort_order = ? WHERE id = ?', [$n + 1, $id]);
            }
        }
        $this->back('/admin/navigation?menu=' . ($i['menu_key'] ?? 'main'));
    }

    // ---------------------------------------------------------------- modules & plugins (superadmin only)
    public function modules(): string
    {
        $rows = db()->all('SELECT * FROM {modules} ORDER BY is_core DESC, id');
        foreach ($rows as &$r) {
            $dir = MCMS_ROOT . ($r['is_core'] ? '/modules/' : '/plugins/') . $r['module_key'];
            $meta = is_file($dir . '/module.json') ? json_decode((string) file_get_contents($dir . '/module.json'), true) : (is_file($dir . '/plugin.json') ? json_decode((string) file_get_contents($dir . '/plugin.json'), true) : []);
            $r['name'] = $meta['name'] ?? $r['module_key'];
            $r['description'] = $meta['description'] ?? '';
            $r['present'] = is_dir($dir);
        }
        return $this->page(t('Modules & plugins'), 'modules', 'modules', ['rows' => $rows]);
    }

    public function moduleToggle(array $p): void
    {
        $m = db()->one('SELECT * FROM {modules} WHERE module_key = ?', [$p['key']]);
        if ($m && !$m['is_core']) {
            db()->exec('UPDATE {modules} SET enabled = 1 - enabled WHERE id = ?', [$m['id']]);
            audit('module.toggle', $m['module_key'] . ' → ' . ($m['enabled'] ? 'off' : 'on'));
            flash('success', t('Saved. The change is active immediately.'));
        } else {
            flash('danger', t('Core modules cannot be disabled.'));
        }
        $this->back('/admin/modules');
    }

    // ---------------------------------------------------------------- pages
    public function pages(): string
    {
        return $this->page(t('Pages'), 'pages', 'pages', ['rows' => db()->all('SELECT id, slug, title, published, updated_at FROM {pages} ORDER BY title')]);
    }

    public function pageEdit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT * FROM {pages} WHERE id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            $this->back('/admin/pages');
        }
        return $this->page($row ? t('Edit page') : t('New page'), 'pages', 'page_edit', ['row' => $row]);
    }

    public function pageSave(): void
    {
        $id = (int) input('id', 0);
        $title = mb_substr((string) input('title'), 0, 160);
        $slug = strtolower(trim((string) input('slug'), '-'));
        if ($slug === '') {
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title)) ?? '', '-');
        }
        $slug = substr($slug, 0, 100);
        $err = null;
        if ($title === '') {
            $err = t('Title is required.');
        } elseif (!preg_match('/^[a-z0-9\-]{1,100}$/', $slug)) {
            $err = t('Slug may only contain a-z, 0-9 and "-".');
        } elseif (db()->val('SELECT id FROM {pages} WHERE slug = ? AND id <> ?', [$slug, $id])) {
            $err = t('That slug is already used.');
        }
        if ($err) {
            flash('danger', $err);
            $this->back($id ? '/admin/pages/' . $id : '/admin/pages/new');
        }
        $content = Sanitizer::html((string) ($_POST['content'] ?? ''));
        $layout = self::pick((string) input('layout'), ['none', 'left', 'right', 'both'], '');
        $pub = empty($_POST['published']) ? 0 : 1;
        if ($id && db()->val('SELECT id FROM {pages} WHERE id = ?', [$id])) {
            db()->exec('UPDATE {pages} SET title=?, slug=?, content=?, layout=?, published=?, updated_at=? WHERE id=?', [$title, $slug, $content, $layout, $pub, now(), $id]);
        } else {
            $id = db()->insert('INSERT INTO {pages} (slug, title, content, layout, published, created_at, updated_at) VALUES (?,?,?,?,?,?,?)', [$slug, $title, $content, $layout, $pub, now(), now()]);
        }
        audit('page.save', $slug);
        flash('success', t('Page saved.'));
        $this->back('/admin/pages/' . $id);
    }

    public function pageDelete(array $p): void
    {
        $id = (int) $p['id'];
        db()->exec("DELETE FROM {menu_items} WHERE target_type = 'page' AND target_ref = ?", [(string) $id]);
        db()->exec('DELETE FROM {pages} WHERE id = ?', [$id]);
        audit('page.delete', '#' . $id);
        flash('success', t('Page deleted.'));
        $this->back('/admin/pages');
    }
}
