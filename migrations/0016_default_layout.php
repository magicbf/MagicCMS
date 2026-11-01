<?php
/**
 * Existing sites: sites still on the old default ("panels on both sides") move to "right only" and all their left-hand
 * panels are appended to the right column (nothing disappears). The Account panel is switched off – the header has login/register.
 * Sites that chose a different layout on purpose are left alone.
 */
return function (MagicCMS\Core\Database $db): void {
    $mode = json_decode((string) $db->val("SELECT `value` FROM {settings} WHERE `key` = 'layout.mode'"), true);
    if ($mode === null || $mode === 'both') {
        $db->exec("INSERT INTO {settings} (`key`, `value`) VALUES ('layout.mode', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)", [json_encode('right')]);
        $next = (int) $db->val("SELECT COALESCE(MAX(sort_order), 0) FROM {panels} WHERE position = 'right'");
        foreach ($db->all("SELECT id FROM {panels} WHERE position = 'left' ORDER BY sort_order, id") as $r) {
            $db->exec("UPDATE {panels} SET position = 'right', sort_order = ? WHERE id = ?", [++$next, $r['id']]);
        }
    }
    $db->exec("UPDATE {panels} SET enabled = 0 WHERE panel_key = 'user_box'");
};
