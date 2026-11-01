<?php /** @var ?array $row @var array $games */
$r = $row ?? ['id' => 0, 'opponent' => '', 'opponent_tag' => '', 'game' => $games[0] ?? 'Main roster', 'competition' => '', 'map' => '', 'status' => 'finished', 'score_us' => '', 'score_them' => '', 'played_at' => now(), 'vod_url' => '', 'report' => '']; ?>
<form class="card" method="post" action="<?= e(url('admin/matches/save')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><h2 class="card-title"><?= e($r['id'] ? t('Edit match') : t('New match')) ?></h2><div class="card-body">
  <div class="form-grid">
    <div class="form-row"><label class="label"><?= e(t('Opponent')) ?></label><input class="input" name="opponent" maxlength="80" value="<?= e($r['opponent']) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Opponent tag')) ?></label><input class="input" name="opponent_tag" maxlength="12" value="<?= e($r['opponent_tag']) ?>" placeholder="IRW"></div>
    <div class="form-row"><label class="label"><?= e(t('Date & time')) ?></label><input class="input" type="datetime-local" name="played_at" value="<?= e(utc_to_local($r['played_at'])) ?>" required></div>
    <div class="form-row"><label class="label"><?= e(t('Status')) ?></label><select class="select" name="status"><option value="finished" <?= $r['status'] === 'finished' ? 'selected' : '' ?>><?= e(t('Finished')) ?></option><option value="upcoming" <?= $r['status'] === 'upcoming' ? 'selected' : '' ?>><?= e(t('Upcoming')) ?></option></select></div>
    <div class="form-row"><label class="label"><?= e(t('Our score')) ?></label><input class="input" type="number" min="0" max="999" name="score_us" value="<?= e($r['score_us']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Their score')) ?></label><input class="input" type="number" min="0" max="999" name="score_them" value="<?= e($r['score_them']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Game / roster')) ?></label><input class="input" name="game" maxlength="60" list="games" value="<?= e($r['game']) ?>"><datalist id="games"><?php foreach ($games as $g): ?><option value="<?= e($g) ?>"><?php endforeach ?></datalist></div>
    <div class="form-row"><label class="label"><?= e(t('Competition')) ?></label><input class="input" name="competition" maxlength="120" value="<?= e($r['competition']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('Map')) ?></label><input class="input" name="map" maxlength="80" value="<?= e($r['map']) ?>"></div>
    <div class="form-row"><label class="label"><?= e(t('VOD link (optional)')) ?></label><input class="input" name="vod_url" maxlength="300" value="<?= e($r['vod_url']) ?>" placeholder="https://…"></div>
  </div>
  <div class="form-row"><label class="label"><?= e(t('Match report (HTML – unsafe tags are removed)')) ?></label><textarea class="textarea mono" name="report" rows="8"><?= e((string) $r['report']) ?></textarea></div>
  <button class="btn btn-primary"><?= e(t('Save match')) ?></button> <a class="btn btn-ghost" href="<?= e(url('admin/matches')) ?>"><?= e(t('Back')) ?></a>
</div></form>
