<?php
/** Grandfather existing users as verified, register the messages module, permissions and mail defaults. */
return function (MagicCMS\Core\Database $db): void {
    $db->exec('UPDATE {users} SET email_verified_at = created_at WHERE email_verified_at IS NULL');
    $db->exec("INSERT IGNORE INTO {modules} (module_key, enabled, is_core) VALUES ('messages', 1, 1)");
    $db->exec("INSERT IGNORE INTO {permissions} (perm_key, label) VALUES ('pm.send', 'Send private messages')");
    foreach (['member', 'moderator', 'admin'] as $role) {
        $db->exec("INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = ? AND p.perm_key = 'pm.send'", [$role]);
    }
    $name = json_decode((string) $db->val("SELECT `value` FROM {settings} WHERE `key` = 'site.name'"), true) ?: 'MagicCMS';
    foreach (['auth.email_verify' => false, 'mail.driver' => 'mail', 'mail.from_name' => $name, 'mail.from_email' => '', 'mail.smtp_host' => '', 'mail.smtp_port' => 587, 'mail.smtp_secure' => 'tls', 'mail.smtp_user' => '', 'mail.smtp_pass' => ''] as $k => $v) {
        $db->exec('INSERT IGNORE INTO {settings} (`key`, `value`) VALUES (?, ?)', [$k, json_encode($v, JSON_UNESCAPED_UNICODE)]);
    }
};
