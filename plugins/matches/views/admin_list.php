<?php /** @var array $rows @var string $status @var int $page @var int $pages @var int $count */ use MagicCMS\Plugins\Matches\Matches; ?>
<div class="card"><h2 class="card-title"><?= e(t('Recent Matches panel')) ?></h2>
<form class="card-body inline-form" method="post" action="<?= e(url('admin/matches/settings')) ?>"><?= csrf_field() ?>
  <label class="label" for="pc"><?= e(t('Show the last')) ?></label>
  <select class="select" id="pc" name="panel_count"><?php foreach (Matches::PANEL_OPTIONS as $n): ?><option value="<?= $n ?>" <?= $count === $n ? 'selected' : '' ?>><?= $n ?> <?= e(t('matches')) ?></option><?php endforeach ?></select>
  <button class="btn btn-sm btn-primary"><?= e(t('Save')) ?></button>
  <small class="help"><?= e(t('The panel always links to the full list on the Matches page. Add or move the panel in Admin → Panels.')) ?></small></form></div>
<div class="forum-bar"><div class="forum-bar-links"><a class="btn btn-primary btn-sm" href="<?= e(url('admin/matches/new')) ?>">+ <?= e(t('New match')) ?></a>
  <?php foreach (['' => 'All', 'finished' => 'Finished', 'upcoming' => 'Upcoming'] as $k => $l): ?><a class="btn btn-sm <?= $status === $k ? 'btn-primary' : 'btn-ghost' ?>" href="<?= e(url('admin/matches' . ($k ? '?status=' . $k : ''))) ?>"><?= e(t($l)) ?></a><?php endforeach ?></div>
  <a class="btn btn-sm btn-ghost" href="<?= e(url('matches')) ?>"><?= e(t('Public page')) ?> →</a></div>
<div class="card"><div class="card-body table-wrap"><table class="table"><thead><tr><th><?= e(t('Date')) ?></th><th><?= e(t('Opponent')) ?></th><th><?= e(t('Competition')) ?></th><th><?= e(t('Score')) ?></th><th></th><th></th></tr></thead><tbody>
<?php foreach ($rows as $m): $res = Matches::result($m); ?>
<tr><td><?= e(fmt_date($m['played_at'])) ?></td><td><strong><?= e($m['opponent']) ?></strong> <span class="muted small"><?= e($m['opponent_tag']) ?></span></td><td><?= e($m['competition']) ?></td>
<td><?= $res ? (int) $m['score_us'] . ' : ' . (int) $m['score_them'] : '–' ?></td><td><span class="badge match-badge-<?= e($res ?? 'up') ?>"><?= e($res ? t(ucfirst($res)) : t('Upcoming')) ?></span></td>
<td class="actions"><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/matches/' . (int) $m['id'])) ?>"><?= e(t('Edit')) ?></a><form method="post" action="<?= e(url('admin/matches/delete/' . (int) $m['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Delete this match?')) ?>">✕</button></form></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="muted"><?= e(t('No matches yet.')) ?></td></tr><?php endif ?></tbody></table></div></div>
<?= render('pagination', ['page' => $page, 'pages' => $pages, 'base' => url('admin/matches') . ($status ? '?status=' . $status : '')]) ?>
