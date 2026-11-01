<?php /** @var array $u */ ?>
<div class="card profile-card"><h1 class="card-title"><?= e($u['username']) ?></h1>
<div class="card-body profile-head">
  <img class="avatar avatar-xl" src="<?= e(avatar_url($u, 256)) ?>" alt="" width="128" height="128">
  <div>
    <span class="badge role-<?= e($u['role_slug']) ?>"><?= e($u['role_name']) ?></span>
    <p class="muted"><?= e(t('Member since')) ?> <?= e(fmt_date($u['created_at'], 'Y-m-d')) ?> · <?= e(t('Last seen')) ?> <?= e(time_ago($u['last_seen'])) ?></p>
    <?php if ($u['bio'] !== ''): ?><p><?= nl2br(e($u['bio'])) ?></p><?php endif ?>
    <?php if ($u['signature'] !== ''): ?><p class="post-signature"><?= e($u['signature']) ?></p><?php endif ?>
    <?php if (pm_enabled() && can('pm.send') && (int) user()['id'] !== (int) $u['id']): ?><a class="btn btn-primary btn-sm" href="<?= e(url('messages/new?to=' . rawurlencode($u['username']))) ?>">✉ <?= e(t('Send message')) ?></a> <?php endif ?>
    <?php if (user() && user()['id'] === (int) $u['id']): ?><a class="btn btn-ghost btn-sm" href="<?= e(url('profile')) ?>"><?= e(t('Edit profile')) ?></a><?php endif ?>
  </div>
</div></div>
