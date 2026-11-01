<?php
/** Logo-only header option: carry over the old "show name next to a custom logo" switch. */
return function (MagicCMS\Core\Database $db): void {
    $old = $db->val("SELECT `value` FROM {settings} WHERE `key` = 'site.logo_show_name'");
    if ($old !== null && json_decode((string) $old) === false) {
        $db->exec("INSERT INTO {settings} (`key`, `value`) VALUES ('site.show_name', 'false') ON DUPLICATE KEY UPDATE `value` = 'false'");
    }
};
