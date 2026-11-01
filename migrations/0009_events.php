<?php
/** Events plugin: registration, permissions, sample events, menu entry and panel. */
return function (MagicCMS\Core\Database $db): void {
    $now = gmdate('Y-m-d H:i:s');
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core, version) VALUES ('events', 1, 0, '1.0.0')");
    foreach (['admin.events' => 'Manage events and tournaments', 'events.signup' => 'Sign up for events'] as $k => $l) {
        $db->exec('INSERT IGNORE INTO {permissions} (perm_key, label) VALUES (?,?)', [$k, $l]);
    }
    foreach (['member' => ['events.signup'], 'moderator' => ['events.signup'], 'admin' => ['events.signup', 'admin.events']] as $role => $keys) {
        foreach ($keys as $k) {
            $db->exec('INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = ? AND p.perm_key = ?', [$role, $k]);
        }
    }
    $author = (int) $db->val('SELECT u.id FROM {users} u JOIN {roles} r ON r.id = u.role_id ORDER BY r.level DESC, u.id LIMIT 1');
    $d = fn(int $days, int $hour) => gmdate('Y-m-d H:i:s', strtotime(gmdate('Y-m-d', time() + $days * 86400) . " $hour:00:00 UTC"));
    $ev = [
        ['Spring Cup – open tournament', 'tournament', '<p>Our community tournament: single elimination, best of three. Sign up with your team captain and get ready for a weekend of fierce matches.</p>', 'Online · Discord lobby', $d(5, 17), $d(5, 22), 8, 1],
        ['Team training', 'training', '<p>Weekly scrim block. Roster members only – be on voice 10 minutes early.</p>', 'Discord · Voice 1', $d(2, 19), $d(2, 21), null, 1],
        ['Season finale stream', 'stream', '<p>Watch the finale live with commentary from the coaching staff.</p>', 'Twitch', $d(9, 20), $d(9, 23), null, 0],
        ['Community game night', 'event', '<p>Relaxed night of custom games and good company. Everyone is welcome.</p>', 'Online', $d(-12, 19), $d(-12, 22), null, 0],
    ];
    foreach ($ev as [$title, $type, $desc, $loc, $start, $end, $max, $open]) {
        $db->exec(
            'INSERT INTO {events} (title, type, description, location, starts_at, ends_at, max_participants, signup_open, status, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [$title, $type, $desc, $loc, $start, $end, $max, $open, 'published', $author ?: null, $now, $now]
        );
    }
    $menu = (int) $db->val("SELECT id FROM {menus} WHERE menu_key = 'main'");
    if ($menu) {
        $ord = (int) $db->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu]);
        $db->exec("INSERT INTO {menu_items} (menu_id, label, icon, target_type, target_ref, visibility, sort_order) VALUES (?,?,?,'module','events',?,?)", [$menu, 'Events', '🗓️', json_encode(['who' => 'all', 'mobile' => true]), $ord]);
    }
    $ord = (int) $db->val("SELECT COALESCE(MAX(sort_order),0)+1 FROM {panels} WHERE position = 'right'");
    $db->exec("INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES ('upcoming_events', 'Upcoming Events', 'right', ?, 1, ?)", [$ord, json_encode(['who' => 'all', 'mobile' => true])]);
};
