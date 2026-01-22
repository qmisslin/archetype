<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Email;
use Archetype\Core\Auth;
use Archetype\Core\Router;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Sends an email to one or multiple recipients.',
    'params' => [
        'dest' => ['type' => 'string|array', 'required' => true, 'desc' => 'Email(s) of recipient(s)'],
        'obj' => ['type' => 'string', 'required' => true, 'desc' => 'Subject line'],
        'content' => ['type' => 'string', 'required' => true, 'desc' => 'Body content'],
        'type' => ['type' => 'string', 'required' => false, 'default' => 'text', 'desc' => 'text or html']
    ],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN', 'EDITOR']);

$body = $router->getBody();

if (empty($body['dest']) || empty($body['obj']) || empty($body['content'])) {
    APIHelper::error('Missing fields: dest, obj, content');
}

$dest = is_array($body['dest']) ? $body['dest'] : explode(',', $body['dest']);
$type = $body['type'] ?? 'text';

try {
    Email::send($dest, $body['obj'], $type, $body['content']);
    APIHelper::success(['message' => 'Email sent']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}
