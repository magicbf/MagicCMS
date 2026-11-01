<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Messages\Controller;

return function (Router $r): void {
    $a = ['auth' => true];
    $send = ['auth' => true, 'perm' => 'pm.send'];
    $c = Controller::class;
    $r->get('/messages', [$c, 'inbox'], $a);
    $r->get('/messages/new', [$c, 'compose'], $send);
    $r->post('/messages/new', [$c, 'create'], $send);
    $r->get('/messages/{id:\d+}', [$c, 'thread'], $a);
    $r->post('/messages/{id:\d+}/reply', [$c, 'reply'], $send);
    $r->post('/messages/{id:\d+}/delete', [$c, 'delete'], $a);
    $r->post('/messages/block/{uid:\d+}', [$c, 'block'], $a);
    $r->post('/messages/unblock/{uid:\d+}', [$c, 'unblock'], $a);
};
