<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Email;
use Archetype\Core\Auth;
use Archetype\Core\Router;

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
