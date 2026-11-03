<?php
use MagicCMS\Core\{Hooks, Router};
use MagicCMS\Modules\Maintenance\Controller;

return function (Router $r): void {
    Hooks::on('admin.tabs', static function (array $tabs): array {
        $tabs['maintenance'] = ['/admin/maintenance', 'Backups & updates', 'admin.system'];
        return $tabs;
    });
    $c = Controller::class;
    $p = ['perm' => 'admin.system'];
    $l = $p + ['layout' => 'none'];
    $r->get('/admin/maintenance', [$c, 'index'], $l);
    $r->post('/admin/maintenance/backup', [$c, 'create'], $p);
    $r->post('/admin/maintenance/backup/download', [$c, 'download'], $p);
    $r->post('/admin/maintenance/backup/delete', [$c, 'delete'], $p);
    $r->post('/admin/maintenance/backup/restore', [$c, 'restore'], $p);
    $r->post('/admin/maintenance/backup/upload', [$c, 'upload'], $p);
    $r->post('/admin/maintenance/settings', [$c, 'settings'], $p);
    $r->get('/admin/maintenance/update', [$c, 'update'], $l);
    $r->post('/admin/maintenance/update/check', [$c, 'check'], $p);
    $r->post('/admin/maintenance/update/source', [$c, 'sourceSave'], $p);
    $r->post('/admin/maintenance/update/branches', [$c, 'branches'], $p);
    $r->post('/admin/maintenance/update/prepare', [$c, 'prepare'], $p);
    $r->post('/admin/maintenance/update/cancel', [$c, 'cancel'], $p);
    $r->post('/admin/maintenance/update/mark-current', [$c, 'markCurrent'], $p);
    $r->get('/admin/maintenance/update/review', [$c, 'review'], $l);
    $r->post('/admin/maintenance/update/apply', [$c, 'apply'], $p);
    $r->get('/admin/maintenance/update/finish', [$c, 'finish'], $l);
    $r->post('/admin/maintenance/update/rollback', [$c, 'rollback'], $p);
};
