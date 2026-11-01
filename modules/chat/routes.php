<?php
use MagicCMS\Core\Router;
use MagicCMS\Modules\Chat\Controller;

return function (Router $r): void {
    $r->get('/chat/messages', [Controller::class, 'messages'], ['perm' => 'chat.read']);
    $r->post('/chat/post', [Controller::class, 'post'], ['perm' => 'chat.post']);
    $r->post('/chat/delete/{id:\d+}', [Controller::class, 'delete'], ['perm' => 'chat.moderate']);
};
