<?php
/**
 * Roster: "squad / group" is removed. A member has a Role (the group they are listed under) and a Position (a small pill next to the name).
 * Old roles such as "Captain · Entry" become Role "Captain" + Position "Entry".
 */
return function (MagicCMS\Core\Database $db): void {
    $cols = array_column($db->all('SHOW COLUMNS FROM {roster_members}'), 'Field');
    if (!in_array('position', $cols, true)) {
        $db->exec("ALTER TABLE {roster_members} ADD COLUMN position VARCHAR(40) NOT NULL DEFAULT '' AFTER role");
    }
    foreach ($db->all("SELECT id, role FROM {roster_members} WHERE position = '' AND role <> ''") as $r) {
        $parts = preg_split('/\s*(?:·|\||\/|,|–|—)\s*/u', trim((string) $r['role']), 2) ?: [];
        if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
            $db->exec('UPDATE {roster_members} SET role = ?, position = ? WHERE id = ?', [mb_substr(trim($parts[0]), 0, 60), mb_substr(trim($parts[1]), 0, 40), $r['id']]);
        }
    }
    if (in_array('squad', $cols, true)) {
        try {
            $db->exec('ALTER TABLE {roster_members} DROP INDEX idx_list');
        } catch (\Throwable) {
        }
        $db->exec('ALTER TABLE {roster_members} DROP COLUMN squad');
        try {
            $db->exec('ALTER TABLE {roster_members} ADD KEY idx_list (is_active, sort_order)');
        } catch (\Throwable) {
        }
    }
    // the panel: new default title, no more squad grouping, role order becomes a site-wide setting
    $db->exec("UPDATE {panels} SET title = 'Our Team' WHERE panel_key = 'roster_squad' AND title = 'Our Squad'");
    foreach ($db->all("SELECT id, settings FROM {panels} WHERE panel_key = 'roster_squad' AND settings IS NOT NULL") as $p) {
        $s = json_decode((string) $p['settings'], true);
        if (!is_array($s)) {
            continue;
        }
        if (($s['group_by'] ?? '') === 'squad') {
            $s['group_by'] = 'role';
        }
        if (!empty($s['role_order']) && !$db->val("SELECT `value` FROM {settings} WHERE `key` = 'roster.role_order'")) {
            $db->exec("INSERT INTO {settings} (`key`, `value`) VALUES ('roster.role_order', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)", [json_encode((string) $s['role_order'])]);
        }
        unset($s['squad'], $s['role_order'], $s['show_tags']);
        $db->exec('UPDATE {panels} SET settings = ? WHERE id = ?', [json_encode($s, JSON_UNESCAPED_UNICODE), $p['id']]);
    }
};
