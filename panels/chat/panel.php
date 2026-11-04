<?php /** @var array $settings */
if (!can('chat.read')) return; ?>
<div class="chat" data-chat data-endpoint="<?= e(url('chat/messages')) ?>" data-post="<?= e(url('chat/post')) ?>" data-delete="<?= e(url('chat/delete/')) ?>" data-edit="<?= e(url('chat/edit/')) ?>" data-interval="<?= max(3, (int) $settings['interval']) ?>" data-me="<?= e(user()['username'] ?? '') ?>">
  <div class="chat-top"><span class="chat-live"><i aria-hidden="true"></i><?= e(t('Live')) ?></span><span class="chat-stats"><b data-chatters>0</b> <?= e(t('chatting')) ?> · <b data-today>0</b> <?= e(t('messages / 24 h')) ?></span></div>
  <div class="chat-wrap">
    <div class="chat-log" role="log" aria-live="polite"><p class="chat-empty"><?= e(t('No messages yet – say hello 👋')) ?></p></div>
    <button type="button" class="chat-new" hidden>↓ <span><?= e(t('New messages')) ?></span></button>
  </div>
  <?php if (can('chat.post')): ?>
  <form class="chat-form">
    <div class="chat-field"><input class="input chat-input" data-emoji maxlength="300" placeholder="<?= e(t('Say something…')) ?>" aria-label="<?= e(t('Message')) ?>" autocomplete="off"><span class="chat-count" aria-hidden="true">300</span></div>
    <button class="btn btn-primary btn-sm chat-send" aria-label="<?= e(t('Send')) ?>"><span><?= e(t('Send')) ?></span><i aria-hidden="true">➤</i></button>
  </form>
  <?php else: ?><p class="muted chat-login"><a href="<?= e(url('login')) ?>"><?= e(t('Log in')) ?></a> <?= e(t('to join the chat.')) ?></p><?php endif ?>
</div>
