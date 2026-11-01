<?php
/** Backups & updates: module registration + the (superadministrator-only) permission key. */
return function (MagicCMS\Core\Database $db): void {
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core, version) VALUES ('maintenance', 1, 1, '1.0.0')");
    $db->exec("INSERT IGNORE INTO {permissions} (perm_key, label) VALUES ('admin.system', 'Backups, restore and software updates (superadministrator)')");
};
