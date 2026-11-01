<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Admin\Controller;

return function (Router $r): void {
    $c = Controller::class;
    $r->get('/admin', [$c, 'dashboard'], ['perm' => 'admin.access', 'layout' => 'none']);

    $r->get('/admin/settings', [$c, 'settings'], ['perm' => 'admin.settings', 'layout' => 'none']);
    $r->post('/admin/settings', [$c, 'settingsSave'], ['perm' => 'admin.settings']);

    $r->post('/admin/settings/logo', [$c, 'logoUpload'], ['perm' => 'admin.settings']);
    $r->post('/admin/settings/logo/reset', [$c, 'logoReset'], ['perm' => 'admin.settings']);

    $r->post('/admin/settings/mail', [$c, 'mailSave'], ['perm' => 'admin.settings']);
    $r->post('/admin/settings/mail/test', [$c, 'mailTest'], ['perm' => 'admin.settings']);

    $r->get('/admin/appearance', [$c, 'appearance'], ['perm' => 'admin.appearance', 'layout' => 'none']);
    $r->post('/admin/appearance', [$c, 'appearanceSave'], ['perm' => 'admin.appearance']);

    $r->get('/admin/users', [$c, 'users'], ['perm' => 'admin.users', 'layout' => 'none']);
    $r->post('/admin/users/{id:\d+}', [$c, 'userSave'], ['perm' => 'admin.users']);
    $r->post('/admin/users/{id:\d+}/verify', [$c, 'userVerify'], ['perm' => 'admin.users']);

    $r->get('/admin/panels', [$c, 'panels'], ['perm' => 'admin.panels', 'layout' => 'none']);
    $r->post('/admin/panels/add', [$c, 'panelAdd'], ['perm' => 'admin.panels']);
    $r->post('/admin/panels/save/{id:\d+}', [$c, 'panelSave'], ['perm' => 'admin.panels']);
    $r->post('/admin/panels/delete/{id:\d+}', [$c, 'panelDelete'], ['perm' => 'admin.panels']);
    $r->get('/admin/panels/{id:\d+}', [$c, 'panelEdit'], ['perm' => 'admin.panels', 'layout' => 'none']);
    $r->post('/admin/panels/{id:\d+}', [$c, 'panelEditSave'], ['perm' => 'admin.panels']);

    $r->get('/admin/navigation', [$c, 'nav'], ['perm' => 'admin.navigation', 'layout' => 'none']);
    $r->post('/admin/navigation/save', [$c, 'navSave'], ['perm' => 'admin.navigation']);
    $r->get('/admin/navigation/{id:\d+}', [$c, 'navEdit'], ['perm' => 'admin.navigation', 'layout' => 'none']);
    $r->post('/admin/navigation/delete/{id:\d+}', [$c, 'navDelete'], ['perm' => 'admin.navigation']);
    $r->post('/admin/navigation/move/{id:\d+}/{dir:up|down}', [$c, 'navMove'], ['perm' => 'admin.navigation']);

    $r->get('/admin/footer', [$c, 'footer'], ['perm' => 'admin.appearance', 'layout' => 'none']);
    $r->post('/admin/footer', [$c, 'footerSave'], ['perm' => 'admin.appearance']);

    $r->get('/admin/modules', [$c, 'modules'], ['perm' => 'admin.modules', 'layout' => 'none']);
    $r->post('/admin/modules/toggle/{key:[a-z0-9_]+}', [$c, 'moduleToggle'], ['perm' => 'admin.modules']);

    $r->get('/admin/pages', [$c, 'pages'], ['perm' => 'admin.pages', 'layout' => 'none']);
    $r->get('/admin/pages/new', [$c, 'pageEdit'], ['perm' => 'admin.pages', 'layout' => 'none']);
    $r->get('/admin/pages/{id:\d+}', [$c, 'pageEdit'], ['perm' => 'admin.pages', 'layout' => 'none']);
    $r->post('/admin/pages/save', [$c, 'pageSave'], ['perm' => 'admin.pages']);
    $r->post('/admin/pages/delete/{id:\d+}', [$c, 'pageDelete'], ['perm' => 'admin.pages']);
};
