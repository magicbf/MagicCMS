<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Pages\Controller;

return function (Router $r): void {
    $r->get('/p/{slug:[a-z0-9\-]+}', [Controller::class, 'show']);
};
