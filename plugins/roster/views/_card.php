<?php /** @var array $m decoded member */ use MagicCMS\Plugins\Roster\Roster; $photo = upload_url('roster', $m['photo']); ?>
<article class="rm-card">
  <a class="rm-photo" href="<?= e(url('roster/' . (int) $m['id'])) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($photo): ?><img src="<?= e($photo) ?>" alt="" width="320" height="400" loading="lazy"><?php else: ?><span class="rm-ph"><?= e(Roster::initials($m['nickname'])) ?></span><?php endif ?>
    <?php if ($m['country'] !== ''): ?><span class="rm-flag" title="<?= e($m['country']) ?>"><?= e(Roster::flag($m['country'])) ?></span><?php endif ?>
  </a>
  <div class="rm-info">
    <h3 class="rm-nick"><a href="<?= e(url('roster/' . (int) $m['id'])) ?>"><?= e($m['nickname']) ?></a><?php if ($m['position'] !== ''): ?> <span class="pos-pill"><?= e($m['position']) ?></span><?php endif ?></h3>
    <?php if ($m['socials_a']): ?><div class="rm-socials"><?php foreach ($m['socials_a'] as $k => $v): if (!isset(Roster::SOCIALS[$k]) || Roster::SOCIALS[$k][2] !== 'url') continue; ?><a href="<?= e($v) ?>" target="_blank" rel="noopener noreferrer nofollow" title="<?= e(Roster::SOCIALS[$k][0]) ?>" aria-label="<?= e(Roster::SOCIALS[$k][0]) ?>"><?= e(Roster::SOCIALS[$k][1]) ?></a><?php endforeach ?></div><?php endif ?>
  </div>
</article>
