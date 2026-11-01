<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Notifications\Controller;

return function (Router $r): void {
    $a = ['auth' => true];
    $r->get('/notifications', [Controller::class, 'index'], $a);
    $r->get('/notifications/go/{id:\d+}', [Controller::class, 'go'], $a);
    $r->post('/notifications/read-all', [Controller::class, 'readAll'], $a);
    $r->post('/notifications/clear', [Controller::class, 'clear'], $a);
};
