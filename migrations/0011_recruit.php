<?php
/** Recruitment plugin: registration, permission, sample positions/questions, menu entry. */
return function (MagicCMS\Core\Database $db): void {
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core, version) VALUES ('recruit', 1, 0, '1.0.0')");
    $db->exec("INSERT IGNORE INTO {permissions} (perm_key, label) VALUES ('admin.recruit', 'Review recruitment applications')");
    $db->exec("INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = 'admin' AND p.perm_key = 'admin.recruit'");
    foreach ([['Player', 'Compete with one of our squads.'], ['Coach / analyst', 'Help the team improve.'], ['Content creator', 'Streams, clips, graphics, editing.'], ['Community moderator', 'Keep Discord and the forum friendly.']] as $i => [$t, $d]) {
        $db->exec('INSERT INTO {recruit_positions} (title, description, sort_order) VALUES (?,?,?)', [$t, $d, $i]);
    }
    $q = [
        ['Age', '', 'text', '', 1], ['Country / time zone', 'So we know when you are online.', 'text', '', 1],
        ['Games and ranks', 'Which games do you play and what is your current rank?', 'textarea', '', 1],
        ['Experience', 'Previous teams, tournaments, roles.', 'textarea', '', 0],
        ['Weekly availability', '', 'select', '1–5 hours|5–10 hours|10–20 hours|20+ hours', 1],
        ['Do you have a working microphone and Discord?', '', 'yesno', '', 1],
        ['Why do you want to join us?', '', 'textarea', '', 1],
    ];
    foreach ($q as $i => [$l, $h, $t, $o, $r]) {
        $db->exec('INSERT INTO {recruit_questions} (label, help, qtype, options, required, sort_order) VALUES (?,?,?,?,?,?)', [$l, $h, $t, $o, $r, $i]);
    }
    foreach (['recruit.enabled' => true, 'recruit.require_login' => false, 'recruit.retention_days' => 180, 'recruit.notify_emails' => '',
              'recruit.intro' => 'Want to play, coach or help out? Tell us about yourself – we read every application and answer within a few days.'] as $k => $v) {
        $db->exec('INSERT IGNORE INTO {settings} (`key`, `value`) VALUES (?,?)', [$k, json_encode($v)]);
    }
    $menu = (int) $db->val("SELECT id FROM {menus} WHERE menu_key = 'main'");
    if ($menu) {
        $ord = (int) $db->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu]);
        $db->exec("INSERT INTO {menu_items} (menu_id, label, icon, target_type, target_ref, visibility, sort_order) VALUES (?,?,?,'module','recruit',?,?)", [$menu, 'Join us', '🎯', json_encode(['who' => 'all', 'mobile' => true]), $ord]);
    }
};
