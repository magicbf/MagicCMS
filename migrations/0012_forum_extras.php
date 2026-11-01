<?php
/** Forum extras: permissions for attachments, voting and reactions. */
return function (MagicCMS\Core\Database $db): void {
    $perms = ['forum.attach' => 'Attach files to forum posts', 'forum.vote' => 'Vote in forum polls', 'forum.react' => 'React to forum posts'];
    foreach ($perms as $k => $l) {
        $db->exec('INSERT IGNORE INTO {permissions} (perm_key, label) VALUES (?,?)', [$k, $l]);
        foreach (['member', 'moderator', 'admin'] as $role) {
            $db->exec('INSERT IGNORE INTO {role_permissions} (role_id, permission_id) SELECT r.id, p.id FROM {roles} r, {permissions} p WHERE r.slug = ? AND p.perm_key = ?', [$role, $k]);
        }
    }
};
