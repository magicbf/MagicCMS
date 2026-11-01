<?php /** @var ?array $m @var ?array $r @var int $schema @var int $latest */ ?>
<div class="card"><h2 class="card-title">✅ <?= e(t('Update finished')) ?></h2><div class="card-body">
<?php if ($m || $r): $from = $m['from'] ?? $r['from']; ?>
  <p><?= e(t('MagicCMS was updated from v{a} to v{b}.', ['a' => $from, 'b' => MCMS_VERSION])) ?></p>
  <ul><li><?= e(t('Database schema: {a} (latest {b}).', ['a' => $schema, 'b' => $latest])) ?></li>
  <?php if ($r): ?><li><?= e(t('{n} files installed.', ['n' => $r['files']])) ?></li><?php endif ?>
  <li><?= e(t('Safety copies are in the backup folder: {a} and {b}.', ['a' => $m['dbBackup'] ?? $r['dbBackup'], 'b' => $m['codeBackup'] ?? $r['codeBackup']])) ?></li>
  <li><?= e(t('Maintenance mode is back to what it was before.')) ?></li></ul>
<?php else: ?><p class="muted"><?= e(t('There is nothing to finish.')) ?></p><?php endif ?>
<p><a class="btn btn-primary" href="<?= e(url('admin')) ?>"><?= e(t('Back to the admin area')) ?></a> <a class="btn btn-ghost" href="<?= e(url('admin/maintenance/update')) ?>"><?= e(t('Update page')) ?></a></p>
</div></div>
