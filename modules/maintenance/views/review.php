<?php /** @var array $insp @var array $preflight @var array $meta @var ?array $pending @var string $sha256 @var int $size @var string $installed @var array $commits */
use MagicCMS\Modules\Maintenance\Controller as C;
$src = $meta['source'] ?? null;
$group = static function (array $files): array { $g = []; foreach ($files as $f) { $g[explode('/', $f)[0]][] = $f; } ksort($g); return $g; };
$label = $src && $src['kind'] !== 'feed' ? $src['repo'] : t('uploaded zip file');
$noop = $insp['ok'] && !$insp['todo'] && !$insp['obsolete']; ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/maintenance/update')) ?>">← <?= e(t('Back')) ?></a></p>
<div class="card"><h2 class="card-title"><?= e(t('Review update')) ?> <small>v<?= e(MCMS_VERSION) ?> → v<?= e($insp['version'] ?: '?') ?><?= $insp['commit'] !== '' ? ' · ' . e(substr($insp['commit'], 0, 7)) : '' ?></small></h2><div class="card-body">
<?php foreach ($insp['errors'] as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach ?>
<?php foreach ($preflight as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach ?>
<?php foreach ($insp['warnings'] as $w): ?><div class="alert alert-warning"><?= e($w) ?></div><?php endforeach ?>
<?php if ($insp['ok']): ?>
  <div class="stat-grid upd-stats">
    <div class="stat-tile"><span><?= e(t('Changed')) ?></span><strong><?= count($insp['changed']) ?></strong></div>
    <div class="stat-tile"><span><?= e(t('New')) ?></span><strong><?= count($insp['added']) ?></strong></div>
    <div class="stat-tile"><span><?= e(t('Removed')) ?></span><strong><?= count($insp['obsolete']) ?></strong></div>
    <div class="stat-tile"><span><?= e(t('Already identical')) ?></span><strong><?= (int) $insp['unchanged'] ?></strong></div>
  </div>

  <?php if ($noop): ?>
    <div class="alert alert-success">✔ <?= e(t('Every file of this site already matches this version. There is nothing to install.')) ?></div>
    <div class="actions"><?php if ($insp['commit'] !== ''): ?><form method="post" action="<?= e(url('admin/maintenance/update/mark-current')) ?>"><?= csrf_field() ?><button class="btn btn-primary"><?= e(t('Remember this build and finish')) ?></button></form><?php endif ?>
    <form method="post" action="<?= e(url('admin/maintenance/update/cancel')) ?>"><?= csrf_field() ?><button class="btn btn-ghost"><?= e(t('Close')) ?></button></form></div>
  <?php else: ?>

  <?php if ($commits): ?><details class="upd-commits" open><summary><?= e(t('What is new ({n} changes)', ['n' => count($commits)])) ?></summary><ul><?php foreach ($commits as $c): ?><li><code><?= e($c['sha']) ?></code> <span><?= e($c['msg']) ?></span> <small class="muted"><?= e($c['author']) ?></small></li><?php endforeach ?></ul></details><?php endif ?>
  <?php if ($insp['notes'] !== '' && !$commits): ?><pre class="rc-msg upd-notes"><?= e($insp['notes']) ?></pre><?php endif ?>

  <h3 class="upd-h"><?= e(t('Files that will be changed')) ?></h3>
  <?php foreach ([['added', t('New files'), 'new'], ['changed', t('Changed files'), 'chg'], ['obsolete', t('Files removed after the update succeeded'), 'del']] as [$k, $title, $cls]): if (!$insp[$k]) continue; ?>
  <details class="upd-files" <?= count($insp[$k]) <= 12 ? 'open' : '' ?>><summary><span class="upd-tag <?= $cls ?>"><?= count($insp[$k]) ?></span> <?= e($title) ?></summary>
    <?php foreach ($group($insp[$k]) as $dir => $fs): ?><div class="upd-dir"><b><?= e($dir) ?>/</b> <small class="muted">(<?= count($fs) ?>)</small>
      <ul><?php foreach (array_slice($fs, 0, 60) as $f): ?><li><code><?= e(str_starts_with($f, $dir . '/') ? substr($f, strlen($dir) + 1) : $f) ?></code><?php if ($k !== 'obsolete'): ?> <small class="muted"><?= e(C::size((int) ($insp['files'][$f] ?? 0))) ?></small><?php endif ?><?= in_array($f, $insp['modified'], true) ? ' <span class="badge badge-warning">' . e(t('customised here')) . '</span>' : '' ?></li><?php endforeach ?><?php if (count($fs) > 60): ?><li class="muted">… <?= count($fs) - 60 ?> <?= e(t('more')) ?></li><?php endif ?></ul></div><?php endforeach ?>
  </details>
  <?php endforeach ?>
  <?php if ($insp['kept']): ?><p class="muted small">ℹ <?= e(t('{n} old file(s) are no longer part of this version but were customised on this site, so they are kept: {f}', ['n' => count($insp['kept']), 'f' => implode(', ', array_slice($insp['kept'], 0, 5))])) ?></p><?php endif ?>
  <?php if (!isset(\MagicCMS\Core\Updater::baseline()['shipped'])): ?><p class="muted small">ℹ <?= e(t('Removal of files that newer versions no longer ship starts with the next update (this one records what is installed).')) ?></p><?php endif ?>
  <?php if ($insp['modified']): ?><div class="alert alert-warning mt">⚠ <?= e(t('{n} file(s) were customised on this site and will be replaced. A copy is kept in the code backup, but put changes in a child theme or plugin to keep them safe.', ['n' => count($insp['modified'])])) ?></div><?php endif ?>

  <h3 class="upd-h"><?= e(t('Safety checks passed')) ?></h3>
  <ul class="upd-checks">
    <li class="ok"><?= e(t('Source: {s}', ['s' => $label])) ?><?= $src && ($src['commit'] ?? '') !== '' ? ' · ' . e(t('branch/tag')) . ' <code>' . e($src['ref']) . '</code> · ' . e(t('commit')) . ' <a href="https://github.com/' . e($src['repo']) . '/commit/' . e($src['commit']) . '" target="_blank" rel="noopener noreferrer"><code>' . e(substr($src['commit'], 0, 7)) . '</code></a>' : '' ?></li>
    <li class="ok"><?= e(t('Downloaded over HTTPS from GitHub only (no other host is accepted, also not via redirects).')) ?></li>
    <?php if ($src && ($src['commit'] ?? '') !== ''): ?><li class="ok"><?= e(t('Pinned to the exact commit that was checked – a moving branch cannot swap the files in between.')) ?></li><?php endif ?>
    <li class="ok"><?= e(t('{n} PHP files syntax-checked; every path and file type validated; no symbolic links.', ['n' => (int) $insp['phpChecked']])) ?></li>
    <li class="ok"><?= e(t('Only program folders are touched. Never: config/, uploads/, cache/, backups/, your database content, and any theme/plugin that is not part of this package.')) ?></li>
    <li class="<?= !empty($meta['checksum']) ? 'ok' : 'info' ?>"><?= !empty($meta['checksum']) ? e(t('The download matched its published SHA-256 checksum.')) : e(t('GitHub publishes no checksum for source snapshots; integrity rests on HTTPS and the pinned commit. Only update from repositories you trust.')) ?></li>
    <li class="info"><?= e(t('Package SHA-256:')) ?> <code class="upd-hash"><?= e($sha256) ?></code> (<?= e(C::size($size)) ?>)</li>
    <li class="ok"><?= e(t('Before installing: database backup + a copy of every replaced or removed file. If anything fails, code and database are restored automatically.')) ?></li>
  </ul>

  <?php if (!$preflight && !$pending): ?>
  <form method="post" action="<?= e(url('admin/maintenance/update/apply')) ?>" class="upd-confirm"><?= csrf_field() ?>
    <h3 class="upd-h"><?= e(t('Confirm')) ?></h3>
    <label class="checkbox"><input type="checkbox" name="understand" value="1" required> <?= e(t('I have read the list above. I understand the site goes into maintenance mode for a few seconds and that a backup is made first.')) ?></label>
    <div class="form-grid">
      <div class="form-row"><label class="label" for="u-pw"><?= e(t('Your password')) ?></label><input class="input" id="u-pw" type="password" name="password" autocomplete="current-password" required></div>
      <div class="form-row"><label class="label" for="u-cf"><?= e(t('Type UPDATE')) ?></label><input class="input" id="u-cf" name="confirm" placeholder="UPDATE" autocomplete="off" required></div>
    </div>
    <div class="actions"><button class="btn btn-primary">⚡ <?= e(t('Install update')) ?></button>
      <button class="btn btn-ghost" formaction="<?= e(url('admin/maintenance/update/cancel')) ?>" formnovalidate><?= e(t('Cancel – install nothing')) ?></button></div>
  </form>
  <?php else: ?>
  <form method="post" action="<?= e(url('admin/maintenance/update/cancel')) ?>"><?= csrf_field() ?><button class="btn btn-ghost"><?= e(t('Cancel – install nothing')) ?></button></form>
  <?php endif ?>
  <?php endif ?>
<?php else: ?>
  <form method="post" action="<?= e(url('admin/maintenance/update/cancel')) ?>"><?= csrf_field() ?><button class="btn btn-ghost"><?= e(t('Discard this download')) ?></button></form>
<?php endif ?>
</div></div>
