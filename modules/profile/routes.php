<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Profile\Controller;

return function (Router $r): void {
    $o = ['auth' => true];
    $r->get('/profile', [Controller::class, 'form'], $o);
    $r->post('/profile', [Controller::class, 'save'], $o);
    $r->post('/profile/avatar', [Controller::class, 'avatar'], $o);
    $r->post('/profile/avatar/delete', [Controller::class, 'avatarDelete'], $o);
    $r->post('/profile/password', [Controller::class, 'password'], $o);
};
