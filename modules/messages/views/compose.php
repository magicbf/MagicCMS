<?php /** @var string $to @var string $subject @var string $body */ ?>
<nav class="breadcrumb"><a href="<?= e(url('messages')) ?>"><?= e(t('Messages')) ?></a> <span class="sep">›</span> <span><?= e(t('New message')) ?></span></nav>
<form class="card" method="post" action="<?= e(url('messages/new')) ?>"><?= csrf_field() ?><h1 class="card-title"><?= e(t('New message')) ?></h1><div class="card-body">
  <div class="form-row"><label class="label" for="to"><?= e(t('To (usernames, comma separated, up to {n})', ['n' => MagicCMS\Modules\Messages\Messages::MAX_RECIPIENTS])) ?></label><input class="input" id="to" name="to" value="<?= e($to) ?>" required autocomplete="off" <?= $to === '' ? 'autofocus' : '' ?>></div>
  <div class="form-row"><label class="label" for="sj"><?= e(t('Subject')) ?></label><input class="input" id="sj" name="subject" maxlength="140" value="<?= e($subject) ?>" required <?= $to !== '' ? 'autofocus' : '' ?>></div>
  <?= render('forum/_editor', ['body' => $body, 'rows' => 10]) ?>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Send')) ?></button> <a class="btn btn-ghost" href="<?= e(url('messages')) ?>"><?= e(t('Cancel')) ?></a></p>
</div></form>
