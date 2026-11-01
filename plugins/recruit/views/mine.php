<?php /** @var array $rows */ use MagicCMS\Plugins\Recruit\Recruit; ?>
<section class="page-head"><h1 class="admin-title"><?= e(t('My applications')) ?></h1></section>
<div class="card"><div class="card-body"><?php foreach ($rows as $a): ?><p class="sub-row"><a href="<?= e(url('recruit/status/' . $a['token'])) ?>"><strong><?= e($a['position_title'] ?: t('Application')) ?></strong></a> <span class="badge rc-<?= e($a['status']) ?>"><?= e(Recruit::label($a['status'])) ?></span> <span class="muted"><?= e(fmt_date($a['created_at'])) ?></span></p>
<?php endforeach; if (!$rows): ?><p class="muted"><?= e(t('No applications yet.')) ?> <a href="<?= e(url('recruit')) ?>"><?= e(t('Apply now')) ?></a></p><?php endif ?></div></div>
