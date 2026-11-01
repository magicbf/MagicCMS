<?php
/** Register the notifications module. */
return function (MagicCMS\Core\Database $db): void {
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core) VALUES ('notifications', 1, 1)");
};
