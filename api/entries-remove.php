<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'DELETE',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Permanently removes an entry.',
    'params' => [
        'entryId' => ['type' => 'int', 'required' => true]
    ],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();
$entryId = (int)($body['entryId'] ?? 0);

if (!$entryId) {
    APIHelper::error("Entry ID is required");
}

try {
    Entries::Remove($entryId);
    APIHelper::success(['message' => 'Entry removed']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}