<?php /** @var array $positions @var array $questions @var ?array $existing @var array $v */
use MagicCMS\Plugins\Recruit\Recruit; $u = user(); $q = $v['q'] ?? []; ?>
<section class="page-head"><h1 class="admin-title"><?= e(t('Join us')) ?></h1><p class="lead"><?= e((string) setting('recruit.intro', '')) ?></p></section>
<?php if ($existing): ?><div class="alert alert-info"><?= e(t('You already have an open application ({status}).', ['status' => Recruit::label($existing['status'])])) ?> <a href="<?= e(url('recruit/status/' . $existing['token'])) ?>"><?= e(t('View it')) ?></a></div>
<?php else: ?>
<form class="card recruit-form" method="post" action="<?= e(url('recruit')) ?>"><?= csrf_field() ?><h2 class="card-title"><?= e(t('Application')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Name / nickname')) ?> *</label><input class="input" name="name" maxlength="60" required value="<?= e($v['name'] ?? ($u['username'] ?? '')) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('E-mail')) ?> *</label><input class="input" type="email" name="email" maxlength="190" required value="<?= e($v['email'] ?? ($u['email'] ?? '')) ?>"><small class="help"><?= e(t('Only used to answer your application.')) ?></small></div>
  </div>
  <?php if ($positions): ?><div class="form-row"><label class="label"><?= e(t('Position')) ?> *</label><div class="pos-list">
    <?php foreach ($positions as $i => $p): ?><label class="pos-opt"><input type="radio" name="position" value="<?= (int) $p['id'] ?>" <?= ((int) ($v['position'] ?? 0) === (int) $p['id'] || (!isset($v['position']) && $i === 0)) ? 'checked' : '' ?>><span><strong><?= e($p['title']) ?></strong><small><?= e($p['description']) ?></small></span></label><?php endforeach ?></div></div><?php endif ?>
  <?php foreach ($questions as $qq): $val = (string) ($q[$qq['id']] ?? ''); $nm = 'q[' . (int) $qq['id'] . ']'; ?>
  <div class="form-row"><label class="label"><?= e($qq['label']) ?><?= $qq['required'] ? ' *' : '' ?></label>
    <?php if ($qq['qtype'] === 'textarea'): ?><textarea class="textarea" name="<?= e($nm) ?>" rows="4" maxlength="2000" <?= $qq['required'] ? 'required' : '' ?>><?= e($val) ?></textarea>
    <?php elseif ($qq['qtype'] === 'select'): ?><select class="select" name="<?= e($nm) ?>" <?= $qq['required'] ? 'required' : '' ?>><option value="">—</option><?php foreach (Recruit::options($qq) as $o): ?><option <?= $val === $o ? 'selected' : '' ?>><?= e($o) ?></option><?php endforeach ?></select>
    <?php elseif ($qq['qtype'] === 'yesno'): ?><div class="radio-row"><label><input type="radio" name="<?= e($nm) ?>" value="yes" <?= $val === 'yes' ? 'checked' : '' ?> <?= $qq['required'] ? 'required' : '' ?>> <?= e(t('Yes')) ?></label> <label><input type="radio" name="<?= e($nm) ?>" value="no" <?= $val === 'no' ? 'checked' : '' ?>> <?= e(t('No')) ?></label></div>
    <?php else: ?><input class="input" name="<?= e($nm) ?>" maxlength="300" value="<?= e($val) ?>" <?= $qq['required'] ? 'required' : '' ?>><?php endif ?>
    <?php if ($qq['help'] !== ''): ?><small class="help"><?= e($qq['help']) ?></small><?php endif ?></div>
  <?php endforeach ?>
  <div class="hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
  <p class="mt"><button class="btn btn-primary"><?= e(t('Send application')) ?></button></p>
</div></form>
<?php endif ?>
