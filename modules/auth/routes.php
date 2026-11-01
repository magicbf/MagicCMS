<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Auth\Controller;

return function (Router $r): void {
    $r->get('/login', [Controller::class, 'loginForm'], ['layout' => 'none']);
    $r->post('/login', [Controller::class, 'login'], ['layout' => 'none']);
    $r->get('/register', [Controller::class, 'registerForm'], ['layout' => 'none']);
    $r->post('/register', [Controller::class, 'register'], ['layout' => 'none']);
    $r->post('/logout', [Controller::class, 'logout']);
};
