<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

APIHelper::document([
    'method' => 'DELETE',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Deletes a file (physical) or folder (virtual/recursive).',
    'params' => [
        'fileId' => ['type' => 'int', 'required' => true]
    ],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

if (empty($body['fileId'])) APIHelper::error("FileId is required");

try {
    Uploads::Remove((int)$body['fileId']);
    APIHelper::success(['message' => 'Item deleted']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}