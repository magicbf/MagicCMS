<?php
$members = (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'");
$msgs = (int) db()->val('SELECT COUNT(*) FROM {chat_messages}');
$newest = db()->one("SELECT id, username FROM {users} WHERE status = 'active' ORDER BY id DESC LIMIT 1");
?>
<dl class="stats">
  <div><dt><?= e(t('Members')) ?></dt><dd><?= $members ?></dd></div>
  <div><dt><?= e(t('Shouts')) ?></dt><dd><?= $msgs ?></dd></div>
</dl>
<?php if ($newest): ?><p class="muted"><?= e(t('Newest member')) ?>: <a href="<?= e(url('user/' . $newest['username'])) ?>"><?= e($newest['username']) ?></a></p><?php endif ?>
