<?php /** @var array $backups @var string $dir @var ?array $pending @var bool $zip @var bool $writable @var bool $protected */
use MagicCMS\Modules\Maintenance\Controller as C; ?>
<p class="muted"><?= e(t('Superadministrator only. Backups contain everything in your database – members, hashed passwords, private messages – so keep them private. Settings, content and uploads are never touched by updates.')) ?> <a href="<?= e(url('admin/maintenance/update')) ?>"><strong><?= e(t('Updates')) ?> →</strong></a></p>
<?php if ($pending): ?><div class="alert alert-warning"><?= e(t('An update ({a} → {b}) is waiting to be finished.', ['a' => $pending['from'], 'b' => $pending['to']])) ?> <a href="<?= e(url('admin/maintenance/update')) ?>"><?= e(t('Open')) ?></a></div><?php endif ?>
<?php if (!$writable): ?><div class="alert alert-danger"><?= e(t('The backup folder is not writable: {d}', ['d' => $dir])) ?></div><?php endif ?>
<div class="card"><h2 class="card-title"><?= e(t('Create a backup')) ?></h2><div class="card-body">
  <form class="inline-form" method="post" action="<?= e(url('admin/maintenance/backup')) ?>"><?= csrf_field() ?>
    <button class="btn btn-primary">💾 <?= e(t('Back up the database now')) ?></button>
    <?php if ($zip): ?><label class="checkbox"><input type="checkbox" name="uploads" value="1"> <?= e(t('Include uploads (avatars, news covers, attachments …) as one .zip')) ?></label><?php endif ?>
  </form>
  <p class="muted small mt"><?= e(t('Folder: {d}', ['d' => $dir])) ?> · <?= $protected ? e(t('web access blocked')) : '<span style="color:var(--c-danger)">' . e(t('make sure this folder is not publicly reachable')) . '</span>' ?></p>
  <p class="muted small"><?= e(t('From the command line (cron):')) ?> <code>php tools/backup.php [--uploads]</code></p>
</div></div>
<div class="card"><h2 class="card-title"><?= e(t('Backups')) ?></h2><div class="card-body table-wrap"><table class="table"><thead><tr><th><?= e(t('File')) ?></th><th><?= e(t('Created')) ?></th><th><?= e(t('Size')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($backups as $b): ?><tr><td><code><?= e($b['name']) ?></code><br><small class="muted"><?= $b['type'] === 'full' ? e(t('database + uploads')) : e(t('database')) ?> · <?= e($b['label']) ?></small></td><td><?= e(fmt_date(gmdate('Y-m-d H:i:s', $b['time']))) ?></td><td><?= e(C::size($b['size'])) ?></td>
<td class="actions">
  <form method="post" action="<?= e(url('admin/maintenance/backup/download')) ?>"><?= csrf_field() ?><input type="hidden" name="name" value="<?= e($b['name']) ?>"><button class="btn btn-sm btn-ghost">⬇ <?= e(t('Download')) ?></button></form>
  <details class="report"><summary class="btn btn-sm btn-ghost">↺ <?= e(t('Restore')) ?></summary>
    <form method="post" action="<?= e(url('admin/maintenance/backup/restore')) ?>"><?= csrf_field() ?><input type="hidden" name="name" value="<?= e($b['name']) ?>">
      <p class="small"><?= e(t('Replaces the WHOLE database with this backup (everything created since is lost). A safety backup is made first. Type RESTORE:')) ?></p>
      <input class="input" name="confirm" placeholder="RESTORE" autocomplete="off" required><button class="btn btn-sm btn-danger"><?= e(t('Restore')) ?></button></form></details>
  <form method="post" action="<?= e(url('admin/maintenance/backup/delete')) ?>"><?= csrf_field() ?><input type="hidden" name="name" value="<?= e($b['name']) ?>"><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this backup?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$backups): ?><tr><td colspan="4" class="muted"><?= e(t('No backups yet.')) ?></td></tr><?php endif ?></tbody></table></div></div>
<div class="grid-2">
<form class="card" method="post" action="<?= e(url('admin/maintenance/settings')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Automatic backups')) ?></h2><div class="card-body">
  <div class="form-row"><label class="label"><?= e(t('Back up automatically every … days (0 = off)')) ?></label><input class="input" type="number" min="0" max="90" name="auto_days" value="<?= (int) setting('backup.auto_days', 7) ?>"><small class="help"><?= e(t('Runs when an administrator opens the admin area and the last automatic backup is older than this. For exact schedules use a cron job with tools/backup.php.')) ?></small></div>
  <div class="form-row"><label class="label"><?= e(t('Keep the newest … backups')) ?></label><input class="input" type="number" min="1" max="100" name="keep" value="<?= (int) setting('backup.keep', 10) ?>"><small class="help"><?= e(t('Safety backups made before updates/restores are kept separately (newest 5).')) ?></small></div>
  <div class="form-row"><label class="label"><?= e(t('Update feed (HTTPS URL)')) ?></label><input class="input" name="feed" value="<?= e(setting('update.feed', 'https://api.github.com/repos/magicbf/MagicCMS/releases/latest')) ?>"></div>
  <button class="btn btn-primary"><?= e(t('Save')) ?></button></div></form>
<form class="card" method="post" action="<?= e(url('admin/maintenance/backup/upload')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Upload a backup')) ?></h2><div class="card-body">
  <p class="muted"><?= e(t('Moving to a new server or recovering after a crash: upload a database backup (.sql.gz) made by MagicCMS, then restore it from the list above. Full .zip backups can be copied into the backup folder.')) ?></p>
  <input class="input" type="file" name="backup" accept=".gz,application/gzip" required><p class="mt"><button class="btn btn-ghost"><?= e(t('Upload')) ?></button></p></div></form>
</div>
