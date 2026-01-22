<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

APIHelper::document([
    'method' => 'PATCH',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Updates metadata (name, access) of a file/folder.',
    'params' => [
        'fileId' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'string', 'required' => true],
        'access' => ['type' => 'array', 'required' => false]
    ],
    'returns' => ['message' => 'string']
]);

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

if (empty($body['fileId']) || empty($body['name'])) {
    APIHelper::error("FileId and name are required");
}
$access = $body['access'] ?? ['PUBLIC', 'EDITOR', 'ADMIN'];

try {
    Uploads::Edit((int)$body['fileId'], $body['name'], $access, $user['id']);
    APIHelper::success(['message' => 'Item updated']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}