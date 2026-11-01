<?php
/** Forum seed data: module row, permissions, demo content, menu entry and panel. Runs once. */
return function (MagicCMS\Core\Database $db): void {
    $now = gmdate('Y-m-d H:i:s');
    $db->exec('INSERT IGNORE INTO {modules} (module_key, enabled, is_core) VALUES (?,1,1)', ['forum']);

    $perms = ['forum.post' => 'Create topics and replies', 'forum.report' => 'Report posts', 'forum.moderate' => 'Moderate the forum', 'admin.forum' => 'Manage forum boards'];
    $grant = ['member' => ['forum.post', 'forum.report'], 'moderator' => ['forum.post', 'forum.report', 'forum.moderate'], 'admin' => array_keys($perms)];
    foreach ($perms as $k => $label) {
        $db->exec('INSERT IGNORE INTO {permissions} (perm_key, label) VALUES (?,?)', [$k, $label]);
    }
    foreach ($grant as $role => $keys) {
        foreach ($keys as $k) {
            $db->exec('INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = ? AND p.perm_key = ?', [$role, $k]);
        }
    }

    // Demo structure
    $cat = $db->insert('INSERT INTO {forum_categories} (title, sort_order) VALUES (?,1)', ['Community']);
    $news = $db->insert('INSERT INTO {forum_boards} (category_id, title, description, sort_order, read_level, post_level) VALUES (?,?,?,1,0,80)', [$cat, 'Announcements', 'Official news from the staff', ]);
    $gen = $db->insert('INSERT INTO {forum_boards} (category_id, title, description, sort_order, read_level, post_level) VALUES (?,?,?,2,0,10)', [$cat, 'General Discussion', 'Talk about anything']);

    $author = (int) $db->val("SELECT u.id FROM {users} u JOIN {roles} r ON r.id = u.role_id ORDER BY r.level DESC, u.id LIMIT 1");
    if ($author) {
        $tid = $db->insert("INSERT INTO {forum_topics} (board_id, user_id, title, type, last_post_at, created_at) VALUES (?,?,?,'announce',?,?)", [$news, $author, 'Welcome to the forum!', $now, $now]);
        $pid = $db->insert('INSERT INTO {forum_posts} (topic_id, user_id, body, created_at) VALUES (?,?,?,?)', [$tid, $author,
            "Welcome! This forum supports [b]BBCode[/b]: [i]italic[/i], [u]underline[/u], [url=https://github.com/magicbf/MagicCMS]links[/url], [quote=Staff]quotes[/quote], [code]code blocks[/code] and lists:\n[list]\n[*]Be respectful\n[*]Have fun\n[/list]", $now]);
        $db->exec('UPDATE {forum_topics} SET first_post_id = ?, last_post_id = ? WHERE id = ?', [$pid, $pid, $tid]);
        $db->exec('UPDATE {forum_boards} SET topic_count = 1, post_count = 1, last_post_id = ? WHERE id = ?', [$pid, $news]);
        $db->exec('UPDATE {users} SET forum_posts = 1 WHERE id = ?', [$author]);
    }

    // Menu entry + panel
    $menu = (int) $db->val("SELECT id FROM {menus} WHERE menu_key = 'main'");
    if ($menu) {
        $ord = (int) $db->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu]);
        $db->exec("INSERT INTO {menu_items} (menu_id, label, icon, target_type, target_ref, visibility, sort_order) VALUES (?,?,?,'module','forum',?,?)", [$menu, 'Forum', '💬', json_encode(['who' => 'all', 'mobile' => true]), $ord]);
    }
    $ord = (int) $db->val("SELECT COALESCE(MAX(sort_order),0)+1 FROM {panels} WHERE position = 'right'");
    $db->exec("INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES ('latest_topics', 'Latest Topics', 'right', ?, 1, ?)", [$ord, json_encode(['who' => 'all', 'mobile' => true])]);
};
