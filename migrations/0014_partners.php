<?php
/** Partners module: registration, permission, and the "above footer" panel (enabled by default; empty until logos are added). */
return function (MagicCMS\Core\Database $db): void {
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core, version) VALUES ('partners', 1, 1, '1.0.0')");
    $db->exec("INSERT IGNORE INTO {permissions} (perm_key, label) VALUES ('admin.partners', 'Manage partner logos')");
    $db->exec("INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = 'admin' AND p.perm_key = 'admin.partners'");
    if (!$db->val("SELECT id FROM {panels} WHERE panel_key = 'partners'")) {
        $db->exec("INSERT INTO {panels} (panel_key, title, position, sort_order, enabled, visibility) VALUES ('partners', 'Our partners', 'above_footer', 1, 1, ?)", [json_encode(['who' => 'all', 'mobile' => true])]);
    }
};
