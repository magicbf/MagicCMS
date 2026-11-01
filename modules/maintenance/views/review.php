<?php /** @var array $insp @var array $preflight @var bool $checksum @var ?array $pending */ ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/maintenance/update')) ?>">← <?= e(t('Back')) ?></a></p>
<div class="card"><h2 class="card-title"><?= e(t('Review update')) ?> <small>v<?= e(MCMS_VERSION) ?> → v<?= e($insp['version'] ?: '?') ?></small></h2><div class="card-body">
<?php foreach ($insp['errors'] as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach ?>
<?php foreach ($preflight as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach ?>
<?php foreach ($insp['warnings'] as $w): ?><div class="alert alert-warning"><?= e($w) ?></div><?php endforeach ?>
<?php if ($insp['ok']): ?>
  <ul>
    <li><?= e(t('{n} files will be replaced, {m} new files added.', ['n' => $insp['replace'], 'm' => $insp['new']])) ?></li>
    <li><?= e(t('Never touched: config/, uploads/, cache/, backups/, your database content, and any theme/plugin not contained in the package.')) ?></li>
    <li><?= $checksum ? e(t('The download matched its published checksum.')) : e(t('Integrity: file hashes inside the package were verified; no external checksum was available – only install packages from a source you trust.')) ?></li>
    <li><?= e(t('Before installing: database backup + copy of all replaced files. Migrations run right after; if anything fails, code and database are restored automatically.')) ?></li>
  </ul>
  <?php if ($insp['notes'] !== ''): ?><pre class="rc-msg" style="white-space:pre-wrap"><?= e($insp['notes']) ?></pre><?php endif ?>
  <?php if ($insp['modified']): ?><details><summary><?= e(t('Customised files that will be replaced ({n})', ['n' => count($insp['modified'])])) ?></summary><ul><?php foreach (array_slice($insp['modified'], 0, 40) as $f): ?><li><code><?= e($f) ?></code></li><?php endforeach ?></ul></details><?php endif ?>
  <?php if (!$preflight && !$pending): ?>
  <form method="post" action="<?= e(url('admin/maintenance/update/apply')) ?>" class="inline-form mt"><?= csrf_field() ?>
    <input class="input" name="confirm" placeholder="UPDATE" autocomplete="off" required aria-label="<?= e(t('Type UPDATE to confirm')) ?>"><button class="btn btn-primary"><?= e(t('Install update')) ?></button><small class="muted"><?= e(t('Type UPDATE to confirm. The site is in maintenance mode for a few seconds.')) ?></small></form>
  <?php endif ?>
<?php endif ?>
</div></div>
