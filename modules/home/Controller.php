<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Home;

use MagicCMS\Core\{Appearance, Auth, View};

final class Controller
{
    public function index(): string
    {
        return View::page(t('Home'), render('home/index'), ['class' => 'page-home']);
    }

    /** Save the light/dark preference (account if logged in, cookie always). */
    public function mode(): void
    {
        $m = (string) input('mode');
        if (!in_array($m, ['dark', 'light', 'auto'], true) || !setting('appearance.allow_user_mode', true)) {
            json_out(['ok' => false], 422);
        }
        if (user()) {
            db()->exec('UPDATE {users} SET theme_mode = ? WHERE id = ?', [$m, user()['id']]);
        }
        Auth::cookie('mcms_mode', $m, time() + 365 * 86400);
        json_out(['ok' => true]);
    }

    /** Generated default avatar: initial on a brand-coloured gradient (no image storage needed). */
    public function avatar(array $p): void
    {
        $name = (string) db()->val('SELECT username FROM {users} WHERE id = ?', [(int) $p['id']]);
        $c = Appearance::colors();
        $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';
        $rot = ((int) $p['id'] * 47) % 360;
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=3600');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><linearGradient id="g" gradientTransform="rotate(' . $rot . ' .5 .5)"><stop offset="0" stop-color="' . $c['primary_hi'] . '"/><stop offset=".55" stop-color="' . $c['primary'] . '"/><stop offset="1" stop-color="' . $c['primary_lo'] . '"/></linearGradient></defs>'
            . '<rect width="100" height="100" fill="url(#g)"/><text x="50" y="50" dy=".35em" text-anchor="middle" font-family="Segoe UI,Arial,sans-serif" font-weight="800" font-size="54" fill="' . $c['on_primary'] . '">' . htmlspecialchars($initial, ENT_XML1) . '</text></svg>';
        exit;
    }
}
