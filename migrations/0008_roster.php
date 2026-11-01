<?php
/** Roster plugin: registration, permission, sample squad, menu entry and panel. */
return function (MagicCMS\Core\Database $db): void {
    $now = gmdate('Y-m-d H:i:s');
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core, version) VALUES ('roster', 1, 0, '1.0.0')");
    $db->exec("INSERT IGNORE INTO {permissions} (perm_key, label) VALUES ('admin.roster', 'Manage the team roster')");
    $db->exec("INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = 'admin' AND p.perm_key = 'admin.roster'");

    $m = [
        ['Kyro', 'Captain · Entry', 'Main roster', 'DE', 'Founder and in-game leader. Calm under pressure, loud on comms.', [['K/D', '1.34'], ['Rating', '1.21'], ['Clutches', '48']], ['twitch' => 'https://twitch.tv/', 'x' => 'https://x.com/'], 1],
        ['Vexa', 'Support', 'Main roster', 'SE', 'Utility specialist who always has the perfect smoke.', [['K/D', '1.08'], ['Rating', '1.12'], ['Assists', '212']], ['discord' => 'vexa#0001'], 2],
        ['Rook', 'AWPer', 'Main roster', 'PL', 'Cold-blooded sniper with a flair for highlight reels.', [['K/D', '1.41'], ['Rating', '1.27'], ['HS %', '39']], ['youtube' => 'https://youtube.com/'], 3],
        ['Nova', 'Lurker', 'Main roster', 'FR', 'Newest recruit. Patient, sharp, unpredictable.', [['K/D', '1.19'], ['Rating', '1.15']], [], 4],
        ['Ash', 'Coach', 'Staff', 'GB', 'Strategy, VOD reviews and good tea.', [], ['x' => 'https://x.com/'], 5],
        ['Ember', 'Rifler', 'Academy', 'NL', 'Academy prospect on the rise.', [['K/D', '1.05']], [], 6],
    ];
    foreach ($m as [$nick, $role, $squad, $cc, $bio, $stats, $soc, $ord]) {
        $st = array_map(fn($x) => ['label' => $x[0], 'value' => $x[1]], $stats);
        $db->exec(
            'INSERT INTO {roster_members} (nickname, role, squad, country, bio, joined_on, is_active, sort_order, socials, stats, created_at, updated_at) VALUES (?,?,?,?,?,?,1,?,?,?,?,?)',
            [$nick, $role, $squad, $cc, $bio, gmdate('Y-m-d', time() - $ord * 86400 * 40), $ord, json_encode($soc), json_encode($st), $now, $now]
        );
    }
    $menu = (int) $db->val("SELECT id FROM {menus} WHERE menu_key = 'main'");
    if ($menu) {
        $ord = (int) $db->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu]);
        $db->exec("INSERT INTO {menu_items} (menu_id, label, icon, target_type, target_ref, visibility, sort_order) VALUES (?,?,?,'module','roster',?,?)", [$menu, 'Roster', '🛡️', json_encode(['who' => 'all', 'mobile' => true]), $ord]);
    }
    $ord = (int) $db->val("SELECT COALESCE(MAX(sort_order),0)+1 FROM {panels} WHERE position = 'left'");
    $db->exec("INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES ('roster_squad', 'Our Squad', 'left', ?, 0, ?)", [$ord, json_encode(['who' => 'all', 'mobile' => true])]);
};
