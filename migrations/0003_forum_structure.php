<?php
/** Forum pages hide the site side-panels by default; admins can change this in Admin → Forum. */
return function (MagicCMS\Core\Database $db): void {
    $db->exec("INSERT IGNORE INTO {settings} (`key`, `value`) VALUES ('forum.layout', '\"none\"')");
};
