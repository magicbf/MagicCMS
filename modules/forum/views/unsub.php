<?php /** @var array $p @var string $title */ ?>
<div class="auth-card card"><h1 class="card-title"><?= e(t('Unsubscribe')) ?></h1>
  <form class="card-body" method="post" action="<?= e(url('forum/unsub/' . $p['kind'] . '/' . (int) $p['id'] . '/' . (int) $p['uid'] . '/' . $p['sig'])) ?>"><?= csrf_field() ?>
    <p><?= e($p['kind'] === 'topic' ? t('Stop notifications about new replies in “{t}”?', ['t' => $title]) : t('Stop notifications about new topics in “{t}”?', ['t' => $title])) ?></p>
    <button class="btn btn-primary btn-block"><?= e(t('Yes, unsubscribe')) ?></button><p class="muted center"><a href="<?= e(url('forum')) ?>"><?= e(t('Keep following')) ?></a></p></form></div>
