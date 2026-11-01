<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Notifications;

use MagicCMS\Core\View;

final class Controller
{
    private const PER_PAGE = 30;

    public function index(): string
    {
        $uid = (int) user()['id'];
        if (random_int(1, 40) === 1) { // housekeeping: read ones after 30 days, anything after 90
            db()->exec('DELETE FROM {notifications} WHERE (read_at IS NOT NULL AND read_at < ?) OR created_at < ?', [gmdate('Y-m-d H:i:s', time() - 30 * 86400), gmdate('Y-m-d H:i:s', time() - 90 * 86400)]);
        }
        $total = (int) db()->val('SELECT COUNT(*) FROM {notifications} WHERE user_id = ?', [$uid]);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, (int) input('page', 1)));
        $rows = db()->all('SELECT * FROM {notifications} WHERE user_id = ? ORDER BY id DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), [$uid]);
        return View::page(t('Notifications'), render('notifications/index', ['rows' => $rows, 'page' => $page, 'pages' => $pages]), ['class' => 'page-notifications', 'layout' => 'none']);
    }

    /** Mark one notification read and jump to its target. */
    public function go(array $p): never
    {
        $n = db()->one('SELECT * FROM {notifications} WHERE id = ? AND user_id = ?', [(int) $p['id'], user()['id']]);
        if ($n) {
            db()->exec('UPDATE {notifications} SET read_at = COALESCE(read_at, ?) WHERE id = ?', [now(), $n['id']]);
            redirect($n['url'] !== '' ? $n['url'] : '/notifications');
        }
        redirect('/notifications');
    }

    public function readAll(): never
    {
        db()->exec('UPDATE {notifications} SET read_at = ? WHERE user_id = ? AND read_at IS NULL', [now(), user()['id']]);
        redirect('/notifications');
    }

    public function clear(): never
    {
        db()->exec('DELETE FROM {notifications} WHERE user_id = ? AND read_at IS NOT NULL', [user()['id']]);
        flash('success', t('Read notifications cleared.'));
        redirect('/notifications');
    }
}
