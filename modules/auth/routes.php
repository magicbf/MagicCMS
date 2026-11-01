<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Auth\Controller;

return function (Router $r): void {
    $r->get('/login', [Controller::class, 'loginForm'], ['layout' => 'none']);
    $r->post('/login', [Controller::class, 'login'], ['layout' => 'none']);
    $r->get('/register', [Controller::class, 'registerForm'], ['layout' => 'none']);
    $r->post('/register', [Controller::class, 'register'], ['layout' => 'none']);
    $r->post('/logout', [Controller::class, 'logout']);
    $r->get('/forgot', [Controller::class, 'forgotForm'], ['layout' => 'none']);
    $r->post('/forgot', [Controller::class, 'forgot'], ['layout' => 'none']);
    $r->get('/reset/{token:[a-f0-9]+}', [Controller::class, 'resetForm'], ['layout' => 'none']);
    $r->post('/reset/{token:[a-f0-9]+}', [Controller::class, 'reset'], ['layout' => 'none']);
    $r->get('/verify/resend', [Controller::class, 'resendForm'], ['layout' => 'none']);
    $r->post('/verify/resend', [Controller::class, 'resend'], ['layout' => 'none']);
    $r->get('/verify/{token:[a-f0-9]+}', [Controller::class, 'verify'], ['layout' => 'none']);
};
