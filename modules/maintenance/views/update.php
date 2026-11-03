<?php /** @var ?array $latest @var ?array $pending @var array $history @var array $modified @var bool $zip @var bool $curl @var array $branches @var string $repo @var string $channel @var string $installed @var bool $autoCheck @var bool $customFeed @var array $codeBackups */
$ghUrl = 'https://github.com/' . $repo;
$chBranch = str_starts_with($channel, 'branch:') ? substr($channel, 7) : '';
$state = !$latest ? 'unknown' : (!$latest['newer'] ? 'ok' : ($latest['unknown'] && $latest['kind'] === 'branch' ? 'compare' : 'new')); ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/maintenance')) ?>">← <?= e(t('Backups')) ?></a></p>
<?php if ($pending): ?><div class="alert alert-warning"><?= e(t('An update ({a} → {b}) was started but not finished.', ['a' => $pending['from'], 'b' => $pending['to']])) ?> <a href="<?= e(url('admin/maintenance/update/finish')) ?>"><?= e(t('Finish it')) ?></a></div><?php endif ?>
<?php if (!$zip): ?><div class="alert alert-danger"><?= e(t('The PHP zip extension is missing – updates and full backups are unavailable.')) ?></div><?php endif ?>
<?php if (!$curl): ?><div class="alert alert-warning"><?= e(t('The PHP cURL extension is not installed; a slower fallback is used to talk to GitHub.')) ?></div><?php endif ?>
<?php if ($customFeed): ?><div class="alert alert-info"><?= e(t('A custom update feed is configured. Saving the update source below switches back to GitHub.')) ?></div><?php endif ?>

<section class="card upd-hero"><h2 class="card-title"><?= e(t('Online update')) ?> <small><a href="<?= e($ghUrl) ?>" rel="noopener noreferrer" target="_blank"><?= e($repo) ?></a></small></h2><div class="card-body">
  <div class="upd-versions">
    <div class="upd-ver"><span><?= e(t('Installed')) ?></span><b>v<?= e(MCMS_VERSION) ?></b><small><?= $installed !== '' ? e(t('build')) . ' <code>' . e(substr($installed, 0, 7)) . '</code>' : e(t('build not recorded yet')) ?></small></div>
    <div class="upd-arrow" aria-hidden="true">→</div>
    <div class="upd-ver is-<?= e($state) ?>"><span><?= e(t('On GitHub')) ?></span>
      <?php if ($latest): ?><b>v<?= e($latest['version']) ?></b><small><?= $latest['kind'] === 'release' ? e(t('release')) . ' ' . e($latest['ref']) : e(t('branch')) . ' <code>' . e($latest['ref']) . '</code>' ?><?= $latest['short'] !== '' ? ' · ' . e(t('build')) . ' <code>' . e($latest['short']) . '</code>' : '' ?></small>
      <?php else: ?><b>?</b><small><?= e(t('not checked yet')) ?></small><?php endif ?></div>
  </div>
  <?php if ($latest): ?>
    <p class="upd-state is-<?= e($state) ?>"><?php
      if ($state === 'ok') { echo '✔ ' . e(t('You are up to date.')); }
      elseif ($state === 'compare') { echo '● ' . e(t('This site has not recorded which build it runs. Download the latest build and review exactly which files differ – nothing is installed until you confirm.')); }
      else { echo '⬆ ' . e(t('An update is available.')); } ?></p>
    <?php if ($latest['notes'] !== ''): ?><pre class="rc-msg upd-notes"><?= e($latest['notes']) ?></pre><?php endif ?>
    <?php if ($latest['commits']): ?><details class="upd-commits" <?= count($latest['commits']) <= 6 ? 'open' : '' ?>><summary><?= e($latest['installed'] !== '' ? t('What is new ({n} changes)', ['n' => count($latest['commits'])]) : t('Latest changes')) ?></summary>
      <ul><?php foreach ($latest['commits'] as $c): ?><li><code><?= e($c['sha']) ?></code> <span><?= e($c['msg']) ?></span> <small class="muted"><?= e($c['author']) ?></small></li><?php endforeach ?></ul></details><?php endif ?>
  <?php endif ?>
  <div class="upd-actions">
    <form method="post" action="<?= e(url('admin/maintenance/update/check')) ?>"><?= csrf_field() ?><button class="btn <?= $latest && $latest['newer'] ? '' : 'btn-primary' ?>">🔍 <?= e(t('Check for updates')) ?></button></form>
    <?php if ($latest && $latest['newer']): ?>
    <form method="post" action="<?= e(url('admin/maintenance/update/prepare')) ?>"><?= csrf_field() ?><input type="hidden" name="source" value="online"><button class="btn btn-primary">⬇ <?= e(t('Download and review')) ?></button></form>
    <?php endif ?>
  </div>
  <p class="help"><?= e(t('Downloading only fetches the files into a private staging folder. Nothing on your site changes until you have seen the review and confirmed it.')) ?></p>
  <ol class="upd-flow">
    <li><b><?= e(t('Download')) ?></b> <?= e(t('into a private staging folder, pinned to the exact commit that was checked')) ?></li>
    <li><b><?= e(t('Verify')) ?></b> <?= e(t('HTTPS to GitHub only, every path and file type checked, every PHP file syntax-checked')) ?></li>
    <li><b><?= e(t('Review')) ?></b> <?= e(t('you see every file that will be added, changed or removed')) ?></li>
    <li><b><?= e(t('Confirm')) ?></b> <?= e(t('tick the box, enter your password and type UPDATE')) ?></li>
    <li><b><?= e(t('Back up')) ?></b> <?= e(t('database + a copy of every file that is replaced')) ?></li>
    <li><b><?= e(t('Install')) ?></b> <?= e(t('file by file, each swapped in atomically; migrations run; any failure rolls everything back')) ?></li>
    <li><b><?= e(t('Clean up')) ?></b> <?= e(t('only after the new version has started: files the new version no longer ships are removed')) ?></li>
  </ol>
</div></section>

<form class="card" method="post" action="<?= e(url('admin/maintenance/update/source')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Update source')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label" for="u-repo"><?= e(t('GitHub repository')) ?></label><input class="input" id="u-repo" name="repo" value="<?= e($repo) ?>" placeholder="owner/name" required pattern="[A-Za-z0-9_.\-]{1,39}/[A-Za-z0-9_.\-]{1,100}"><small class="help"><?= e(t('Public repositories only. Files are fetched from github.com over HTTPS; no other host is ever contacted.')) ?></small></div>
    <div class="form-row"><label class="label" for="u-ch"><?= e(t('What to install')) ?></label>
      <select class="select" id="u-ch" name="channel"><option value="auto" <?= $channel === 'auto' ? 'selected' : '' ?>><?= e(t('Automatic (release, else default branch)')) ?></option><option value="release" <?= $channel === 'release' ? 'selected' : '' ?>><?= e(t('Published releases only')) ?></option><option value="branch" <?= $chBranch !== '' ? 'selected' : '' ?>><?= e(t('Newest commit of a branch')) ?></option></select></div>
    <div class="form-row"><label class="label" for="u-br"><?= e(t('Branch')) ?></label>
      <?php if ($branches): ?><select class="select" id="u-br" name="branch"><?php foreach ($branches as $b): ?><option value="<?= e($b) ?>" <?= $b === $chBranch ? 'selected' : '' ?>><?= e($b) ?></option><?php endforeach ?></select>
      <?php else: ?><input class="input" id="u-br" name="branch_custom" value="<?= e($chBranch) ?>" placeholder="main"><?php endif ?>
      <small class="help"><?= e(t('Used with “Newest commit of a branch”.')) ?></small></div>
  </div>
  <label class="checkbox"><input type="checkbox" name="auto_check" value="1" <?= $autoCheck ? 'checked' : '' ?>> <?= e(t('Look for updates automatically (at most every 12 hours when you open the dashboard) and show a hint')) ?></label>
  <div class="actions mt"><button class="btn btn-primary btn-sm"><?= e(t('Save source')) ?></button>
    <button class="btn btn-ghost btn-sm" formaction="<?= e(url('admin/maintenance/update/branches')) ?>" formnovalidate><?= e(t('Load branch list from GitHub')) ?></button></div>
</div></form>

<form class="card" method="post" action="<?= e(url('admin/maintenance/update/prepare')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Install from a zip file')) ?></h2><div class="card-body">
  <p class="muted"><?= e(t('Upload an official MagicCMS release package (it contains release.json). You will see what changes before anything is installed.')) ?></p>
  <input type="hidden" name="source" value="upload"><input class="input" type="file" name="package" accept=".zip" required><p class="mt"><button class="btn btn-ghost"><?= e(t('Upload and review')) ?></button></p></div></form>
<?php if ($modified): ?><div class="card"><h2 class="card-title"><?= e(t('Customised bundled files')) ?></h2><div class="card-body"><p class="muted"><?= e(t('These bundled files differ from the installed release. An update would replace them (copies are kept in the code backup). Put customisations in a child theme or plugin to keep them safe.')) ?></p><ul><?php foreach (array_slice($modified, 0, 40) as $f): ?><li><code><?= e($f) ?></code></li><?php endforeach ?></ul></div></div><?php endif ?>
<?php if ($history): ?><div class="card"><h2 class="card-title"><?= e(t('Update history')) ?></h2><div class="card-body table-wrap"><table class="table"><thead><tr><th><?= e(t('When')) ?></th><th><?= e(t('Version')) ?></th><th><?= e(t('Changes')) ?></th><th><?= e(t('Backups')) ?></th></tr></thead><tbody>
<?php foreach ($history as $h): ?><tr><td><?= e(fmt_date(gmdate('Y-m-d H:i:s', (int) strtotime($h['at'])))) ?></td><td><?= e($h['from']) ?> → <?= e($h['to']) ?><?= !empty($h['commit']) ? ' <code>' . e(substr($h['commit'], 0, 7)) . '</code>' : '' ?></td><td><?= isset($h['count']) ? (int) $h['count'] . ' ' . e(t('files')) : '' ?><?= !empty($h['purged']) ? ' · ' . (int) $h['purged'] . ' ' . e(t('removed')) : '' ?></td><td><small><code><?= e($h['dbBackup']) ?></code><br><code><?= e($h['codeBackup']) ?></code></small></td></tr><?php endforeach ?></tbody></table>
  <details class="report"><summary class="btn btn-sm btn-danger"><?= e(t('Undo the last update')) ?></summary>
    <form method="post" action="<?= e(url('admin/maintenance/update/rollback')) ?>"><?= csrf_field() ?><p class="small"><?= e(t('Puts the program files AND the database back to the state before the last update. Anything posted since the update is lost. Type ROLLBACK:')) ?></p><input class="input" name="confirm" placeholder="ROLLBACK" required autocomplete="off"><button class="btn btn-sm btn-danger"><?= e(t('Undo')) ?></button></form></details>
</div></div><?php endif ?>
