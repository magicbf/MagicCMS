<?php /** @var array $a @var array $answers @var array $notes @var ?array $account @var int $dupes */ use MagicCMS\Plugins\Recruit\Recruit; ?>
<p><a class="btn btn-ghost" href="<?= e(url('admin/recruit')) ?>">← <?= e(t('All applications')) ?></a></p>
<div class="card"><h2 class="card-title"><?= e($a['name']) ?> <span class="badge rc-<?= e($a['status']) ?>"><?= e(Recruit::label($a['status'])) ?></span></h2><div class="card-body">
  <dl class="rc-dl"><dt><?= e(t('E-mail')) ?></dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd><dt><?= e(t('Position')) ?></dt><dd><?= e($a['position_title'] ?: '—') ?></dd>
  <dt><?= e(t('Account')) ?></dt><dd><?= $account ? '<a href="' . e(url('profile/' . rawurlencode($account['username']))) . '">' . e($account['username']) . '</a>' : e(t('Guest')) ?></dd><dt><?= e(t('Sent')) ?></dt><dd><?= e(fmt_date($a['created_at'])) ?></dd></dl>
  <?php if ($dupes): ?><p class="muted">⚠ <?= e(t('{n} other application(s) from this person.', ['n' => $dupes])) ?></p><?php endif ?>
  <?php foreach ($answers as $x): ?><div class="rc-qa"><strong><?= e($x['q']) ?></strong><p><?= nl2br(e($x['type'] === 'yesno' ? ($x['a'] === 'yes' ? t('Yes') : t('No')) : $x['a'])) ?></p></div><?php endforeach ?>
</div></div>
<form class="card" method="post" action="<?= e(url('admin/recruit/' . (int) $a['id'] . '/status')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Decision')) ?></h2><div class="card-body">
  <div class="form-row"><label class="label"><?= e(t('Status')) ?></label><select class="select" name="status"><?php foreach (Recruit::STATUSES as $k => $_): if ($k === 'withdrawn') continue; ?><option value="<?= e($k) ?>" <?= $a['status'] === $k ? 'selected' : '' ?>><?= e(Recruit::label($k)) ?></option><?php endforeach ?></select></div>
  <div class="form-row"><label class="label"><?= e(t('Message to the applicant (optional)')) ?></label><textarea class="textarea" name="message" rows="3" maxlength="500"><?= e($a['status_message']) ?></textarea></div>
  <label class="checkbox"><input type="checkbox" name="notify" value="1" checked> <?= e(t('Also send an e-mail')) ?></label>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Save decision')) ?></button>
  <?php if ($a['status'] === 'accepted'): ?> <a class="btn btn-ghost" href="<?= e(url('admin/roster/new?nickname=' . rawurlencode($a['name']) . ($account ? '&username=' . rawurlencode($account['username']) : ''))) ?>">+ <?= e(t('Add to roster')) ?></a><?php endif ?></p>
</div></form>
<div class="card"><h2 class="card-title"><?= e(t('Internal notes')) ?></h2><div class="card-body">
  <?php foreach ($notes as $n): ?><div class="rc-note"><small class="muted"><?= e($n['username'] ?? '?') ?> · <?= e(fmt_date($n['created_at'])) ?></small><p><?= nl2br(e($n['body'])) ?></p></div><?php endforeach; if (!$notes): ?><p class="muted"><?= e(t('No notes yet. Notes are never shown to the applicant.')) ?></p><?php endif ?>
  <form method="post" action="<?= e(url('admin/recruit/' . (int) $a['id'] . '/note')) ?>"><?= csrf_field() ?><textarea class="textarea" name="body" rows="2" maxlength="2000" required></textarea><p class="mt"><button class="btn btn-ghost"><?= e(t('Add note')) ?></button></p></form>
</div></div>
<form method="post" action="<?= e(url('admin/recruit/' . (int) $a['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="btn btn-danger" data-confirm="<?= e(t('Delete this application permanently?')) ?>"><?= e(t('Delete application')) ?></button></form>
