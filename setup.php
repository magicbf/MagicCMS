<?php
/**
 * MagicCMS one-time installer.
 * Creates the database schema, the Superadministrator and config/config.php,
 * then locks itself (config/installed.lock) and deletes this file.
 */
declare(strict_types=1);

use MagicCMS\Core\Installer;

define('MCMS_ROOT', __DIR__);
require __DIR__ . '/core/Installer.php';

// Hard lock: once installed this script refuses to run.
if (is_file(__DIR__ . '/config/installed.lock')) {
    http_response_code(404);
    exit('<!doctype html><meta charset="utf-8"><title>404</title><p>Not found. (MagicCMS is already installed – delete setup.php.)</p>');
}

session_name('mcms_setup');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'");

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$_SESSION['tok'] ??= bin2hex(random_bytes(16));
function tok(): string { return '<input type="hidden" name="tok" value="' . h($_SESSION['tok']) . '">'; }

$step = (int) ($_SESSION['step'] ?? 1);
$errors = [];
$done = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['tok'], (string) ($_POST['tok'] ?? ''))) {
        $errors[] = 'Session expired – reload the page.';
    } else {
        $act = $_POST['act'] ?? '';
        if ($act === 'req' && Installer::requirementsOk()) {
            $_SESSION['step'] = $step = 2;
        } elseif ($act === 'db') {
            $dbc = [
                'host' => trim($_POST['host'] ?? 'localhost'), 'port' => (int) ($_POST['port'] ?? 3306) ?: 3306,
                'name' => trim($_POST['name'] ?? ''), 'user' => trim($_POST['user'] ?? ''),
                'pass' => (string) ($_POST['pass'] ?? ''), 'prefix' => trim($_POST['prefix'] ?? 'mcms_'), 'charset' => 'utf8mb4',
            ];
            if ($err = Installer::testDb($dbc)) {
                $errors[] = $err;
            } else {
                $_SESSION['db'] = $dbc;
                $_SESSION['step'] = $step = 3;
            }
        } elseif ($act === 'back') {
            $_SESSION['step'] = $step = max(1, $step - 1);
        } elseif ($act === 'install' && isset($_SESSION['db'])) {
            $site = [
                'name' => trim($_POST['site_name'] ?? ''), 'url' => trim($_POST['site_url'] ?? ''),
                'timezone' => trim($_POST['timezone'] ?? 'UTC'), 'theme' => trim($_POST['theme'] ?? 'magic-red'),
            ];
            $admin = [
                'username' => trim($_POST['username'] ?? ''), 'email' => trim($_POST['email'] ?? ''),
                'password' => (string) ($_POST['password'] ?? ''),
            ];
            if ($site['name'] === '' || mb_strlen($site['name']) > 60) $errors[] = 'Please enter a site name (max 60 characters).';
            if (!preg_match('#^https?://[^\s]+$#i', $site['url'])) $errors[] = 'Please enter the full site URL (https://…).';
            if (!in_array($site['timezone'], DateTimeZone::listIdentifiers(), true)) $errors[] = 'Invalid timezone.';
            if (!preg_match('/^[a-z0-9\-]+$/', $site['theme']) || !is_file(__DIR__ . "/themes/{$site['theme']}/theme.json")) $errors[] = 'Invalid theme.';
            if (!preg_match('/^[A-Za-z0-9_\-]{3,20}$/', $admin['username'])) $errors[] = 'Username: 3–20 characters (letters, numbers, _ and -).';
            if (!filter_var($admin['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9.!#$%&\'*+\/=?^_`{|}~\-]+@[A-Za-z0-9.\-]+$/', $admin['email'])) $errors[] = 'Please enter a valid email address.';
            if (strlen($admin['password']) < 8 || strlen($admin['password']) > 72) $errors[] = 'Password must be 8–72 characters.';
            if ($admin['password'] !== (string) ($_POST['password2'] ?? '')) $errors[] = 'The passwords do not match.';
            if (!$errors) {
                if ($err = Installer::install($_SESSION['db'], $site, $admin)) {
                    $errors[] = $err;
                } else {
                    $done = ['url' => rtrim($site['url'], '/'), 'user' => $admin['username']];
                    $_SESSION = [];
                    session_destroy();
                    $step = 4;
                }
            }
        }
    }
}

ob_start();
$steps = ['Requirements', 'Database', 'Site & Superadmin', 'Done'];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>MagicCMS Setup</title>
<style>
:root{--p:#e10613;--hi:#ff4d4d;--lo:#7a0008}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(900px 500px at 85% -10%,rgba(225,6,19,.22),transparent 60%),radial-gradient(700px 400px at -10% 40%,rgba(122,0,8,.3),transparent 60%),#0b0b0d;color:#f4f4f6;font:16px/1.55 system-ui,"Segoe UI",Roboto,sans-serif;padding:24px 16px}
.wrap{max-width:640px;margin:0 auto}.logo{display:flex;align-items:center;gap:12px;margin:8px 0 20px;font:800 2rem/1 "Segoe UI Black","Arial Black",system-ui}.logo b{background:linear-gradient(135deg,var(--hi),var(--p) 55%,var(--lo));-webkit-background-clip:text;background-clip:text;color:transparent}
.logo svg{width:44px;height:44px}
.steps{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}.steps span{flex:1;min-width:110px;text-align:center;font-size:.8rem;padding:.4rem;border-radius:999px;background:#17171c;color:#a0a0aa;border:1px solid #2a2a33}.steps .on{background:linear-gradient(90deg,var(--lo),var(--p));color:#fff;border-color:transparent;font-weight:700}
.card{background:linear-gradient(180deg,#17171c,#121216);border:1px solid #2a2a33;border-radius:20px;overflow:hidden;box-shadow:0 12px 36px rgba(0,0,0,.5)}
.card h2{margin:0;padding:.8rem 1.2rem;background:linear-gradient(90deg,var(--lo),var(--p) 60%,var(--hi));font:800 1.05rem "Segoe UI Black","Arial Black",system-ui;text-transform:uppercase;letter-spacing:.06em}
.body{padding:1.2rem}label{display:block;font-weight:600;font-size:.88rem;margin:.9rem 0 .3rem}small{color:#a0a0aa}
input,select{width:100%;padding:.65rem .8rem;border-radius:12px;border:1px solid #2a2a33;background:#1f1f26;color:#f4f4f6;font:inherit}input:focus,select:focus{outline:2px solid var(--p);outline-offset:1px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}@media(max-width:520px){.row{grid-template-columns:1fr}}
.btn{display:inline-block;margin-top:1.2rem;padding:.7rem 1.4rem;border:0;border-radius:999px;background:linear-gradient(135deg,var(--hi),var(--p) 55%,var(--lo));color:#fff;font:700 1rem inherit;cursor:pointer}.btn.ghost{background:#1f1f26;border:1px solid #2a2a33;margin-right:8px}
.err{background:rgba(255,59,59,.12);border:1px solid #ff3b3b;border-radius:12px;padding:.7rem 1rem;margin-bottom:1rem}.ok{color:#2ecc71}.bad{color:#ff3b3b}.warn{color:#f5a623}
table{width:100%;border-collapse:collapse}td{padding:.4rem .2rem;border-bottom:1px solid #2a2a33}td:last-child{text-align:right}
.note{background:rgba(245,166,35,.12);border:1px solid #f5a623;border-radius:12px;padding:.8rem 1rem;margin-top:1rem}
</style></head><body><div class="wrap">
<div class="logo"><svg viewBox="0 0 200 200"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ff4d4d"/><stop offset=".55" stop-color="#e10613"/><stop offset="1" stop-color="#7a0008"/></linearGradient></defs><rect x="8" y="8" width="184" height="184" rx="44" fill="#111115" stroke="url(#g)" stroke-width="3"/><path d="M42 150V62q0-10 10-4l48 42 48-42q10-6 10 4v88h-24V96l-34 30-34-30v54z" fill="url(#g)"/><path d="M100 24l12 22-12 22-12-22z" fill="#fff"/></svg>
<span>Magic<b>CMS</b> <small style="font:600 .9rem system-ui">Setup</small></span></div>
<div class="steps"><?php foreach ($steps as $i => $s): ?><span class="<?= $i + 1 === $step ? 'on' : '' ?>"><?= $i + 1 ?>. <?= h($s) ?></span><?php endforeach ?></div>
<?php foreach ($errors as $e): ?><div class="err"><?= h($e) ?></div><?php endforeach ?>

<?php if ($step === 1): ?>
<div class="card"><h2>1 · Requirements</h2><div class="body">
<table><?php foreach (Installer::requirements() as [$label, $ok, $info, $hard]): ?>
<tr><td><?= h($label) ?> <small><?= h($info) ?></small></td><td class="<?= $ok ? 'ok' : ($hard ? 'bad' : 'warn') ?>"><?= $ok ? '✔ OK' : ($hard ? '✘ Required' : '! Optional') ?></td></tr>
<?php endforeach ?></table>
<form method="post"><?= tok() ?><input type="hidden" name="act" value="req">
<?php if (Installer::requirementsOk()): ?><button class="btn">Continue →</button><?php else: ?><p class="bad">Fix the required items above, then reload this page.</p><?php endif ?></form>
</div></div>

<?php elseif ($step === 2): $d = $_SESSION['db'] ?? ['host' => 'localhost', 'port' => 3306, 'name' => '', 'user' => '', 'pass' => '', 'prefix' => 'mcms_']; ?>
<div class="card"><h2>2 · Database</h2><div class="body">
<p><small>Create an empty MySQL/MariaDB database first. The connection is tested before continuing.</small></p>
<form method="post" autocomplete="off"><?= tok() ?><input type="hidden" name="act" value="db">
<div class="row"><div><label>Host</label><input name="host" value="<?= h($_POST['host'] ?? $d['host']) ?>" required></div><div><label>Port</label><input name="port" type="number" value="<?= h($_POST['port'] ?? $d['port']) ?>"></div></div>
<label>Database name</label><input name="name" value="<?= h($_POST['name'] ?? $d['name']) ?>" required>
<div class="row"><div><label>User</label><input name="user" value="<?= h($_POST['user'] ?? $d['user']) ?>" required></div><div><label>Password</label><input name="pass" type="password"></div></div>
<label>Table prefix</label><input name="prefix" value="<?= h($_POST['prefix'] ?? $d['prefix']) ?>" pattern="[a-z][a-z0-9_]{0,15}" required>
<button class="btn">Test connection &amp; continue →</button></form>
</div></div>

<?php elseif ($step === 3):
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $guess = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    $themes = glob(__DIR__ . '/themes/*/theme.json') ?: [];
?>
<div class="card"><h2>3 · Site &amp; Superadministrator</h2><div class="body">
<form method="post" autocomplete="off"><?= tok() ?><input type="hidden" name="act" value="install">
<label>Site name</label><input name="site_name" value="<?= h($_POST['site_name'] ?? 'MagicCMS') ?>" required maxlength="60">
<label>Site URL <small>(no trailing slash)</small></label><input name="site_url" value="<?= h($_POST['site_url'] ?? $guess) ?>" required>
<div class="row"><div><label>Timezone</label><select name="timezone"><?php foreach (DateTimeZone::listIdentifiers() as $tz): ?><option <?= ($_POST['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>><?= h($tz) ?></option><?php endforeach ?></select></div>
<div><label>Theme</label><select name="theme"><?php foreach ($themes as $f): $sl = basename(dirname($f)); $m = json_decode((string) file_get_contents($f), true); ?><option value="<?= h($sl) ?>" <?= $sl === 'magic-red' ? 'selected' : '' ?>><?= h($m['name'] ?? $sl) ?></option><?php endforeach ?></select></div></div>
<h3 style="margin:1.4rem 0 0">👑 Superadministrator account</h3><small>The first account is automatically the Superadministrator. You can promote other users to Administrator later.</small>
<label>Username</label><input name="username" value="<?= h($_POST['username'] ?? '') ?>" required pattern="[A-Za-z0-9_\-]{3,20}">
<label>Email</label><input name="email" type="email" value="<?= h($_POST['email'] ?? '') ?>" required>
<div class="row"><div><label>Password <small>(8–72 chars)</small></label><input name="password" type="password" required minlength="8" maxlength="72"></div><div><label>Repeat password</label><input name="password2" type="password" required minlength="8" maxlength="72"></div></div>
<button class="btn" type="submit">Install MagicCMS ✓</button>
</form>
<form method="post"><?= tok() ?><input type="hidden" name="act" value="back"><button class="btn ghost">← Back</button></form>
</div></div>

<?php elseif ($step === 4 && $done): $gone = Installer::selfDelete(); ?>
<div class="card"><h2>✔ Installation complete</h2><div class="body">
<p>MagicCMS is installed. Your Superadministrator account <strong><?= h($done['user']) ?></strong> is ready.</p>
<?php if ($gone): ?><p class="ok">The installer (<code>setup.php</code>) has been deleted automatically.</p>
<?php else: ?><div class="note"><strong>⚠ Delete <code>setup.php</code> manually.</strong><br>Your server did not allow automatic removal. The installer is already locked (<code>config/installed.lock</code>) and cannot be used again, but you should remove the file.</div><?php endif ?>
<p><a class="btn" style="text-decoration:none" href="<?= h($done['url']) ?>/login">Go to login →</a></p>
</div></div>
<?php else: ?><div class="card"><div class="body"><a class="btn" href="setup.php">Restart setup</a></div></div><?php endif ?>
</div></body></html>
<?php
echo ob_get_clean();
