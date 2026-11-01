<?php /** @var array $post @var ?array $r reactions of this post @var bool $own */
use MagicCMS\Modules\Forum\Reactions;
if (!Reactions::enabled()) return;
$counts = $r['counts'] ?? []; $mine = $r['mine'] ?? null; $may = user() && !$own && can('forum.react');
if (!$counts && !$may) return; ?>
<div class="reactions" role="group" aria-label="<?= e(t('Reactions')) ?>">
<?php foreach (Reactions::KINDS as $k => $emo): $n = (int) ($counts[$k] ?? 0); if (!$n && !$may) continue;
  $title = $n ? implode(', ', $r['names'][$k]) . ($n > count($r['names'][$k]) ? ' …' : '') : $k; ?>
  <?php if ($may): ?><form method="post" action="<?= e(url('forum/post/' . (int) $post['id'] . '/react')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="kind" value="<?= e($k) ?>"><button class="react <?= $mine === $k ? 'is-mine' : '' ?> <?= $n ? '' : 'is-zero' ?>" title="<?= e($title) ?>" aria-pressed="<?= $mine === $k ? 'true' : 'false' ?>"><span><?= $emo ?></span><?php if ($n): ?><b><?= $n ?></b><?php endif ?></button></form>
  <?php else: ?><span class="react <?= $mine === $k ? 'is-mine' : '' ?>" title="<?= e($title) ?>"><span><?= $emo ?></span><b><?= $n ?></b></span><?php endif ?>
<?php endforeach ?>
</div>
