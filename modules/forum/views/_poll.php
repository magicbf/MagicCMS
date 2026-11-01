<?php /** @var array $poll @var array $t */
use MagicCMS\Core\Auth; use MagicCMS\Modules\Forum\{Forum, Polls};
$closed = Polls::isClosed($poll, $t); $see = Polls::canSeeResults($poll, $t); $me = user(); $total = max(1, array_sum(array_column($poll['options'], 'votes')));
$canVote = $me && can('forum.vote') && !$closed; $base = url('forum/topic/' . (int) $t['id']);
$owner = $me && (int) $me['id'] === (int) $t['user_id']; $mod = Forum::isMod() && Auth::level() >= (int) $t['author_level']; ?>
<section class="card poll" id="poll"><h2 class="card-title">📊 <?= e($poll['question']) ?></h2><div class="card-body">
<?php if ($canVote): ?><form method="post" action="<?= e($base . '/vote') ?>"><?= csrf_field() ?><?php endif ?>
<?php foreach ($poll['options'] as $o): $pct = $see ? round($o['votes'] * 100 / $total) : 0; $mineO = in_array((int) $o['id'], $poll['mine'], true); ?>
  <div class="poll-opt <?= $mineO ? 'is-mine' : '' ?>">
    <?php if ($canVote): ?><label class="poll-pick"><input type="<?= $poll['multiple'] ? 'checkbox' : 'radio' ?>" name="opt[]" value="<?= (int) $o['id'] ?>" <?= $mineO ? 'checked' : '' ?>> <span><?= e($o['label']) ?></span></label>
    <?php else: ?><span class="poll-pick"><?= $mineO ? '✔ ' : '' ?><?= e($o['label']) ?></span><?php endif ?>
    <?php if ($see): ?><div class="poll-bar" aria-hidden="true"><i style="width:<?= $pct ?>%"></i></div><span class="poll-num"><?= $pct ?>% <small>(<?= (int) $o['votes'] ?>)</small></span><?php endif ?>
  </div>
<?php endforeach ?>
<?php if ($canVote): ?><p class="mt"><button class="btn btn-primary"><?= e($poll['mine'] ? t('Change my vote') : t('Vote')) ?></button> <?php if ($poll['multiple']): ?><small class="muted"><?= e(t('Up to {n} choices', ['n' => $poll['max_choices']])) ?></small><?php endif ?></p></form><?php endif ?>
<p class="muted small">
  <?= e(t('{n} voter(s)', ['n' => $poll['voters']])) ?> ·
  <?= $closed ? e(t('Poll ended')) : ($poll['closes_at'] ? e(t('Ends {when}', ['when' => fmt_date($poll['closes_at'])])) : e(t('Open'))) ?>
  <?php if (!$see && !$closed): ?> · <?= e($poll['results'] === 'after_close' ? t('Results appear when the poll has ended.') : t('Vote to see the results.')) ?><?php endif ?>
  <?php if (!$me): ?> · <a href="<?= e(url('login?next=' . rawurlencode('/forum/topic/' . (int) $t['id']))) ?>"><?= e(t('Log in to vote')) ?></a><?php endif ?>
</p>
<?php if ($owner || $mod): ?><div class="inline-form">
  <form method="post" action="<?= e($base . '/poll/close') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost"><?= e($poll['is_closed'] ? t('Reopen poll') : t('End poll now')) ?></button></form>
  <?php if ($mod || $poll['voters'] === 0): ?><form method="post" action="<?= e($base . '/poll/delete') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-danger" data-confirm="<?= e(t('Remove the poll and all votes?')) ?>"><?= e(t('Remove poll')) ?></button></form><?php endif ?>
</div><?php endif ?>
</div></section>
