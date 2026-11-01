<?php
/** News + Hero seed: modules, permissions, sample categories/posts, menu entry, default hero slides, panel. */
return function (MagicCMS\Core\Database $db): void {
    $now = gmdate('Y-m-d H:i:s');
    $set = fn(string $k, $v) => $db->exec("INSERT IGNORE INTO {settings} (`key`, `value`) VALUES (?, ?)", [$k, json_encode($v, JSON_UNESCAPED_UNICODE)]);
    $get = fn(string $k, $d = '') => ($v = $db->val('SELECT `value` FROM {settings} WHERE `key` = ?', [$k])) === null ? $d : json_decode((string) $v, true);

    foreach (['news', 'hero'] as $m) {
        $db->exec('INSERT IGNORE INTO {modules} (module_key, enabled, is_core) VALUES (?,1,1)', [$m]);
    }
    $perms = ['news.comment' => 'Comment on news', 'news.moderate' => 'Delete news comments', 'admin.news' => 'Manage news', 'admin.hero' => 'Manage the hero banner'];
    $grant = ['member' => ['news.comment'], 'moderator' => ['news.comment', 'news.moderate'], 'admin' => array_keys($perms)];
    foreach ($perms as $k => $label) {
        $db->exec('INSERT IGNORE INTO {permissions} (perm_key, label) VALUES (?,?)', [$k, $label]);
    }
    foreach ($grant as $role => $keys) {
        foreach ($keys as $k) {
            $db->exec('INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = ? AND p.perm_key = ?', [$role, $k]);
        }
    }

    $set('news.cover_style', 'auto');
    $set('hero.config', [
        'enabled' => true, 'placement' => 'home', 'source' => 'slides', 'height' => 'normal', 'width' => 'contained',
        'transition' => 'fade', 'interval' => 7, 'autoplay' => true, 'pause' => true, 'arrows' => true, 'dots' => true,
        'effect' => 'embers', 'parallax' => true,
    ]);

    // Categories (colour + icon; covers are generated when no picture is uploaded)
    $cats = [['Match Reports', 'match-reports', '#ff5238', '⚔️', 'blades'], ['Team News', 'team-news', '#7cc4ff', '👥', 'orbits'], ['Patch Notes', 'patch-notes', '#3ddc84', '🛠️', 'mesh'], ['Media', 'media', '#c792ea', '🎬', 'waves']];
    $cid = [];
    foreach ($cats as $i => [$name, $slug, $color, $icon, $style]) {
        $cid[$slug] = $db->insert('INSERT INTO {news_categories} (name, slug, color, icon, cover_style, sort_order) VALUES (?,?,?,?,?,?)', [$name, $slug, $color, $icon, $style, $i + 1]);
    }
    $author = (int) $db->val('SELECT u.id FROM {users} u JOIN {roles} r ON r.id = u.role_id ORDER BY r.level DESC, u.id LIMIT 1');
    if ($author) {
        $posts = [
            ['Welcome to our new website', 'welcome-to-our-new-website', 'team-news', 1, 'The new home of the team is live: news, a full forum, member profiles and much more.', '<p>Welcome! This is the new home of the team. Here you will find <strong>match reports</strong>, roster changes and patch notes, and you can discuss everything in the <a href="/forum">forum</a>.</p><p>Edit or delete this post in <em>Admin → News</em>.</p>', 0],
            ['Season opener: a statement win', 'season-opener-a-statement-win', 'match-reports', 1, 'A clean 3–0 to start the season. Here is how it went down.', '<p>Strong communication and disciplined rounds made the difference. Full breakdown and VODs coming soon.</p>', 2],
            ['Patch 1.4 – what changes for us', 'patch-1-4-what-changes-for-us', 'patch-notes', 0, 'Balance changes, new maps and what we will adjust in scrims.', '<p>The latest patch shifts the meta. We will rework our default setups and test new compositions this week.</p>', 5],
        ];
        foreach ($posts as [$title, $slug, $cat, $feat, $excerpt, $body, $daysAgo]) {
            $at = gmdate('Y-m-d H:i:s', time() - $daysAgo * 86400);
            $db->exec(
                "INSERT INTO {news} (category_id, author_id, title, slug, excerpt, body, status, featured, publish_at, created_at, updated_at) VALUES (?,?,?,?,?,?,'published',?,?,?,?)",
                [$cid[$cat], $author, $title, $slug, $excerpt, $body, $feat, $at, $at, $at]
            );
        }
    }

    // Default hero slides (no images needed – gradient + effects)
    $name = (string) $get('site.name', 'MagicCMS');
    $tag = (string) $get('site.tagline', '');
    $desc = (string) $get('site.description', '');
    $slides = [
        ['Official website', $tag !== '' ? $tag : $name, $desc, 'Meet the members', 'members', 'Open the forum', 'forum', 'left'],
        ['Latest news', 'Follow our journey', 'Match reports, roster updates and patch notes – straight from the team.', 'Read the news', 'news', '', '', 'center'],
        ['Community', 'Join the discussion', 'Talk strategy, find scrim partners and meet the squad in our forum.', 'Enter the forum', 'forum', 'Meet the members', 'members', 'right'],
    ];
    foreach ($slides as $i => [$eb, $h, $sub, $b1, $r1, $b2, $r2, $align]) {
        $db->exec(
            "INSERT INTO {hero_slides} (sort_order, eyebrow, headline, subline, align, btn1_label, btn1_type, btn1_ref, btn2_label, btn2_type, btn2_ref, created_at) VALUES (?,?,?,?,?,?,'module',?,?,'module',?,?)",
            [$i + 1, $eb, $h, $sub, $align, $b1, $r1, $b2, $r2, $now]
        );
    }

    // Menu entry + panel
    $menu = (int) $db->val("SELECT id FROM {menus} WHERE menu_key = 'main'");
    if ($menu) {
        $ord = (int) $db->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {menu_items} WHERE menu_id = ?', [$menu]);
        $db->exec("INSERT INTO {menu_items} (menu_id, label, icon, target_type, target_ref, visibility, sort_order) VALUES (?,?,?,'module','news',?,?)", [$menu, 'News', '📰', json_encode(['who' => 'all', 'mobile' => true]), $ord]);
    }
    $ord = (int) $db->val("SELECT COALESCE(MAX(sort_order),0)+1 FROM {panels} WHERE position = 'left'");
    $db->exec("INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES ('latest_news', 'Latest News', 'left', ?, 1, ?)", [$ord, json_encode(['who' => 'all', 'mobile' => true])]);
};
