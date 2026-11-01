<?php /** @var array $a @var array $answers */ use MagicCMS\Plugins\Recruit\Recruit; ?>
<section class="page-head"><h1 class="admin-title"><?= e(t('Your application')) ?></h1></section>
<div class="card"><div class="card-body">
  <p><span class="badge rc-<?= e($a['status']) ?>"><?= e(Recruit::label($a['status'])) ?></span> <span class="muted"><?= e(t('Updated {when}', ['when' => time_ago($a['updated_at'])])) ?></span></p>
  <?php if ($a['status_message'] !== ''): ?><blockquote class="rc-msg"><?= nl2br(e($a['status_message'])) ?></blockquote><?php endif ?>
  <dl class="rc-dl"><dt><?= e(t('Name')) ?></dt><dd><?= e($a['name']) ?></dd><dt><?= e(t('Position')) ?></dt><dd><?= e($a['position_title'] ?: '—') ?></dd><dt><?= e(t('Sent')) ?></dt><dd><?= e(fmt_date($a['created_at'])) ?></dd></dl>
  <?php foreach ($answers as $x): ?><div class="rc-qa"><strong><?= e($x['q']) ?></strong><p><?= nl2br(e($x['type'] === 'yesno' ? ($x['a'] === 'yes' ? t('Yes') : t('No')) : $x['a'])) ?></p></div><?php endforeach ?>
  <?php if (in_array($a['status'], Recruit::ACTIVE, true)): ?><form method="post" action="<?= e(url('recruit/status/' . $a['token'] . '/withdraw')) ?>"><?= csrf_field() ?><button class="btn btn-danger" data-confirm="<?= e(t('Withdraw your application?')) ?>"><?= e(t('Withdraw application')) ?></button></form><?php endif ?>
</div></div>
