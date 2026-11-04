<?php /** @var array $m @var ?array $u */ use MagicCMS\Plugins\Roster\Roster; $photo = upload_url('roster', $m['photo']); ?>
<nav class="breadcrumb"><a href="<?= e(url('roster')) ?>"><?= e(t('Roster')) ?></a> <span class="sep">›</span> <span><?= e($m['nickname']) ?></span></nav>
<article class="card rm-detail"><div class="rm-detail-grid">
  <div class="rm-detail-photo"><?php if ($photo): ?><img src="<?= e($photo) ?>" alt="<?= e($m['nickname']) ?>" width="640" height="800"><?php else: ?><span class="rm-ph big"><?= e(Roster::initials($m['nickname'])) ?></span><?php endif ?></div>
  <div class="rm-detail-body">
    <span class="eyebrow"><?= e($m['role'] !== '' ? $m['role'] : t('Team')) ?><?= !$m['is_active'] ? ' · ' . e(t('Former member')) : '' ?></span>
    <h1 class="page-title"><?= $m['country'] !== '' ? '<span title="' . e($m['country']) . '">' . e(Roster::flag($m['country'])) . '</span> ' : '' ?><?= e($m['nickname']) ?><?php if ($m['position'] !== ''): ?> <span class="pos-pill pos-pill-lg"><?= e($m['position']) ?></span><?php endif ?></h1>
    <p><?php if ($m['real_name'] !== ''): ?><span class="muted"><?= e($m['real_name']) ?></span><?php endif ?><?php if ($m['joined_on']): ?> <span class="muted small">· <?= e(t('Joined')) ?> <?= e(fmt_date($m['joined_on'] . ' 00:00:00', 'M Y')) ?></span><?php endif ?></p>
    <?php if ($m['bio'] !== ''): ?><p class="rm-bio"><?= nl2br(e($m['bio'])) ?></p><?php endif ?>
    <?php if ($m['stats_a']): ?><div class="stat-grid"><?php foreach ($m['stats_a'] as $s): ?><div class="stat-tile"><span><?= e($s['label']) ?></span><strong><?= e($s['value']) ?></strong></div><?php endforeach ?></div><?php endif ?>
    <?php if ($m['socials_a']): ?><div class="rm-social-list"><?php foreach ($m['socials_a'] as $k => $v): if (!isset(Roster::SOCIALS[$k])) continue; [$label, $icon, $kind] = Roster::SOCIALS[$k]; ?>
      <?php if ($kind === 'url'): ?><a class="btn btn-sm btn-ghost" href="<?= e($v) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($icon) ?> <?= e($label) ?></a><?php else: ?><span class="btn btn-sm btn-ghost"><?= e($icon) ?> <?= e($label) ?>: <?= e($v) ?></span><?php endif ?><?php endforeach ?></div><?php endif ?>
    <?php if ($u): ?><p class="mt"><a class="btn btn-primary btn-sm" href="<?= e(url('user/' . $u['username'])) ?>"><?= e(t('Site profile')) ?></a><?php if (pm_enabled() && can('pm.send') && (int) user()['id'] !== (int) $u['id']): ?> <a class="btn btn-ghost btn-sm" href="<?= e(url('messages/new?to=' . rawurlencode($u['username']))) ?>">✉ <?= e(t('Send message')) ?></a><?php endif ?></p><?php endif ?>
  </div></div></article>
<?php if (can('admin.roster')): ?><p><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/roster/' . (int) $m['id'])) ?>">✎ <?= e(t('Edit member')) ?></a></p><?php endif ?>
