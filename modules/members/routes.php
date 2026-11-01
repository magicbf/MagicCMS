<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Members\Controller;

return function (Router $r): void {
    $r->get('/members', [Controller::class, 'index']);
    $r->get('/user/{name:[A-Za-z0-9_\-]+}', [Controller::class, 'show']);
};
