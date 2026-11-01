<?php /** @var array $settings */
if (!can('chat.read')) return; ?>
<div class="chat" data-chat data-endpoint="<?= e(url('chat/messages')) ?>" data-post="<?= e(url('chat/post')) ?>" data-delete="<?= e(url('chat/delete/')) ?>" data-interval="<?= max(3, (int) $settings['interval']) ?>">
  <div class="chat-log" role="log" aria-live="polite"></div>
  <?php if (can('chat.post')): ?>
  <form class="chat-form"><input class="input chat-input" maxlength="300" placeholder="<?= e(t('Say something…')) ?>" aria-label="<?= e(t('Message')) ?>" autocomplete="off"><button class="btn btn-primary btn-sm"><?= e(t('Send')) ?></button></form>
  <?php else: ?><p class="muted"><a href="<?= e(url('login')) ?>"><?= e(t('Log in')) ?></a> <?= e(t('to join the chat.')) ?></p><?php endif ?>
</div>
