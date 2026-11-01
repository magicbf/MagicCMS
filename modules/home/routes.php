<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Home\Controller;

return function (Router $r): void {
    $r->get('/', [Controller::class, 'index']);
    $r->post('/appearance/mode', [Controller::class, 'mode']);
    $r->get('/emoji-data', static function (): void {
        header('Cache-Control: public, max-age=3600');
        json_out(MagicCMS\Core\Emoji::picker());
    });
    $r->get('/avatar/{id:\d+}.svg', [Controller::class, 'avatar']);
};
