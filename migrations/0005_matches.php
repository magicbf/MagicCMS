<?php
/**
 * 1) Permission hardening for existing installs: moderators moderate ONLY the forum.
 * 2) Matches plugin: registration, permissions, settings, sample data, menu entry, panel.
 */
return function (MagicCMS\Core\Database $db): void {
    $now = gmdate('Y-m-d H:i:s');

    // 1) moderators lose chat + news-comment moderation (admins keep it)
    $db->exec("DELETE rp FROM {role_permissions} rp JOIN {roles} r ON r.id = rp.role_id JOIN {permissions} p ON p.id = rp.permission_id
               WHERE r.slug = 'moderator' AND p.perm_key IN ('chat.moderate', 'news.moderate')");

    // 2) matches plugin (not a core module → can be disabled in Admin → Modules)
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core, version) VALUES ('matches', 1, 0, '1.0.0')");
    foreach (['admin.matches' => 'Manage matches', 'admin.modules' => 'Manage modules and plugins'] as $k => $label) {
        $db->exec('INSERT IGNORE INTO {permissions} (perm_key, label) VALUES (?,?)', [$k, $label]);
    }
    // admins manage matches; only the superadministrator (implicit all) manages modules/plugins
    $db->exec("INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = 'admin' AND p.perm_key = 'admin.matches'");
    $db->exec("INSERT IGNORE INTO {settings} (`key`, `value`) VALUES ('matches.panel_count', '5')");

    $author = (int) $db->val('SELECT u.id FROM {users} u JOIN {roles} r ON r.id = u.role_id ORDER BY r.level DESC, u.id LIMIT 1');
    $m = [
        ['Iron Wolves', 'IRW', 'Season 7 League', 'Blackout', 'finished', 3, 1, -1, 'Clean ranked win. The decider went our way after a disciplined second half.'],
        ['Blackout', 'BLK', 'Season 7 League', 'Citadel', 'finished', 1, 3, -4, 'A tough loss in the rematch. Review the VODs before Thursday.'],
        ['Nightfall', 'NTF', 'Weekly Cup', 'Ember', 'finished', 2, 2, -7, ''],
        ['Void Reapers', 'VRP', 'Weekly Cup', 'Citadel', 'finished', 3, 0, -10, ''],
        ['Neon Syndicate', 'NSY', 'Season 7 League', 'Ember', 'finished', 3, 2, -14, ''],
        ['Ashen Crown', 'ASH', 'Open Qualifier', 'Blackout', 'finished', 0, 3, -18, ''],
        ['Static Bloom', 'STB', 'Season 7 League', 'Citadel', 'finished', 3, 1, -22, ''],
        ['Lunar Drift', 'LND', 'Weekly Cup', 'Ember', 'finished', 3, 2, -26, ''],
        ['Hollow Point', 'HPT', 'Season 7 League', 'Blackout', 'finished', 2, 3, -30, ''],
        ['Cinder Guard', 'CDG', 'Season 7 League', 'Citadel', 'finished', 3, 0, -34, ''],
        ['Rift Hunters', 'RFT', 'Season 7 Playoffs', 'Ember', 'upcoming', null, null, 3, ''],
    ];
    foreach ($m as [$opp, $tag, $comp, $map, $st, $us, $them, $days, $report]) {
        $at = gmdate('Y-m-d H:i:s', time() + $days * 86400);
        $db->exec(
            'INSERT INTO {matches} (opponent, opponent_tag, game, competition, map, status, score_us, score_them, played_at, report, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$opp, $tag, 'Main roster', $comp, $map, $st, $us, $them, $at, $report !== '' ? '<p>' . $report . '</p>' : null, $author ?: null, $now, $now]
        );
    }

    $menu = (int) $db->val("SELECT id FROM {menus} WHERE menu_key = 'main'");
    if ($menu) {
        $ord = (int) $db->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu]);
        $db->exec("INSERT INTO {menu_items} (menu_id, label, icon, target_type, target_ref, visibility, sort_order) VALUES (?,?,?,'module','matches',?,?)", [$menu, 'Matches', '⚔️', json_encode(['who' => 'all', 'mobile' => true]), $ord]);
    }
    $ord = (int) $db->val("SELECT COALESCE(MAX(sort_order),0)+1 FROM {panels} WHERE position = 'right'");
    $db->exec("INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES ('recent_matches', 'Recent Matches', 'right', ?, 1, ?)", [$ord, json_encode(['who' => 'all', 'mobile' => true])]);
};
