<?php /** @var ?array $row @var array $people @var ?array $bracket */ use MagicCMS\Plugins\Events\{Bracket, Events};
$r = $row ?? ['id' => 0, 'title' => '', 'type' => 'event', 'description' => '', 'location' => '', 'url' => '', 'starts_at' => gmdate('Y-m-d H:00:00', time() + 7 * 86400), 'ends_at' => null, 'max_participants' => null, 'signup_open' => 1, 'signup_deadline' => null, 'status' => 'published']; ?>
<form class="card" method="post" action="<?= e(url('admin/events/save')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><h2 class="card-title"><?= e($r['id'] ? t('Edit event') : t('New event')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Title')) ?></label><input class="input" name="title" maxlength="140" value="<?= e($r['title']) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Type')) ?></label><select class="select" name="type"><?php foreach (Events::TYPES as $k => [$l, $i]): ?><option value="<?= $k ?>" <?= $r['type'] === $k ? 'selected' : '' ?>><?= e($i . ' ' . t($l)) ?></option><?php endforeach ?></select></div>
    <div class="form-row"><label class="label"><?= e(t('Starts')) ?></label><input class="input" type="datetime-local" name="starts_at" value="<?= e(utc_to_local($r['starts_at'])) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Ends (optional)')) ?></label><input class="input" type="datetime-local" name="ends_at" value="<?= e(utc_to_local($r['ends_at'])) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Location')) ?></label><input class="input" name="location" maxlength="160" value="<?= e($r['location']) ?>" placeholder="Online · Discord"></div>
    <div class="form-row"><label class="label"><?= e(t('Link (optional)')) ?></label><input class="input" name="url" maxlength="300" value="<?= e($r['url']) ?>" placeholder="https://…"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Description (HTML – unsafe tags are removed)')) ?></label><textarea class="textarea mono" name="description" rows="7"><?= e((string) $r['description']) ?></textarea></div>
  <fieldset class="btn-fields"><legend class="label"><?= e(t('Sign-up')) ?></legend>
    <label class="checkbox"><input type="checkbox" name="signup_open" value="1" <?= $r['signup_open'] ? 'checked' : '' ?>> <?= e(t('Members can sign up')) ?></label>
    <div class="form-grid mt"><div class="form-row"><label class="label"><?= e(t('Maximum participants (empty = unlimited; extra sign-ups join a waiting list)')) ?></label><input class="input" type="number" min="1" max="999" name="max_participants" value="<?= e((string) $r['max_participants']) ?>"></div>
      <div class="form-row"><label class="label"><?= e(t('Sign-up deadline (optional)')) ?></label><input class="input" type="datetime-local" name="signup_deadline" value="<?= e(utc_to_local($r['signup_deadline'])) ?>"></div></div></fieldset>
  <div class="form-row"><label class="label"><?= e(t('Visibility')) ?></label><select class="select" name="status"><option value="published" <?= $r['status'] === 'published' ? 'selected' : '' ?>><?= e(t('Published')) ?></option><option value="draft" <?= $r['status'] === 'draft' ? 'selected' : '' ?>><?= e(t('Draft (only admins see it)')) ?></option></select></div>
  <button class="btn btn-primary"><?= e(t('Save event')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/events')) ?>"><?= e(t('Back')) ?></a>
</div></form>

<?php if ($r['id']): ?>
<div class="card"><h2 class="card-title"><?= e(t('Sign-ups')) ?> <small>(<?= count($people) ?>)</small></h2><div class="card-body">
  <?php foreach ($people as $p): ?><form class="inline" method="post" action="<?= e(url('admin/events/' . (int) $r['id'] . '/remove/' . (int) $p['id'])) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-ghost" title="<?= e(t('Remove')) ?>"><?= e($p['username']) ?> · <?= e($p['status']) ?> ✕</button></form> <?php endforeach; if (!$people): ?><p class="muted"><?= e(t('Nobody has signed up yet.')) ?></p><?php endif ?></div></div>

<?php if ($r['type'] === 'tournament'): ?>
<div class="card" id="bracket"><h2 class="card-title">🏆 <?= e(t('Bracket')) ?></h2><div class="card-body">
  <form method="post" action="<?= e(url('admin/events/' . (int) $r['id'] . '/bracket')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="generate">
    <p class="muted"><?= e(t('Single elimination for 2–{n} participants. Top seeds get byes when the field is not a power of two. Creating a bracket replaces the current one.', ['n' => Bracket::MAX])) ?></p>
    <div class="form-row"><label class="label"><?= e(t('Participants – one per line, best seed first')) ?></label><textarea class="textarea" name="participants" rows="6" placeholder="Team Alpha&#10;Team Bravo&#10;…"></textarea></div>
    <label class="checkbox"><input type="checkbox" name="shuffle" value="1"> <?= e(t('Shuffle (random seeding)')) ?></label>
    <p class="inline-form mt"><button class="btn btn-primary btn-sm"><?= e(t('Create bracket from this list')) ?></button><button class="btn btn-ghost btn-sm" name="source" value="signups"><?= e(t('Create from “going” sign-ups')) ?></button></p></form>
  <?php if ($bracket): $rounds = $bracket['rounds']; $total = count($rounds); ?>
  <hr class="sep-line"><form method="post" action="<?= e(url('admin/events/' . (int) $r['id'] . '/bracket')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="scores">
    <?php foreach ($rounds as $ri => $round): ?><h3 class="section-title small"><?= e(Bracket::roundName($ri, $total)) ?></h3><div class="b-admin"><?php foreach ($round as $mi => $m): $ready = $m['a'] !== null && $m['b'] !== null; ?>
      <div class="b-admin-match"><span class="b-an"><?= e($m['a'] ?? ($ri ? t('TBD') : '—')) ?></span><?php if ($ready): ?><input class="input narrow" type="number" min="0" max="999" name="sa[<?= $ri ?>][<?= $mi ?>]" value="<?= $m['sa'] !== null ? (int) $m['sa'] : '' ?>" aria-label="score"><b>:</b><input class="input narrow" type="number" min="0" max="999" name="sb[<?= $ri ?>][<?= $mi ?>]" value="<?= $m['sb'] !== null ? (int) $m['sb'] : '' ?>" aria-label="score"><?php else: ?><b>vs</b><?php endif ?><span class="b-an"><?= e($m['b'] ?? ($m['a'] !== null && $ri === 0 ? 'BYE' : t('TBD'))) ?></span></div><?php endforeach ?></div><?php endforeach ?>
    <p class="inline-form mt"><button class="btn btn-primary btn-sm"><?= e(t('Save results')) ?></button><button class="btn btn-danger btn-sm" name="action" value="reset" data-confirm="<?= e(t('Remove the bracket?')) ?>"><?= e(t('Remove bracket')) ?></button></p></form>
  <?php endif ?></div></div>
<?php endif ?>
<?php endif ?>
