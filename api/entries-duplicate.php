<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Duplicates an existing entry.',
    'params' => [
        'entryId' => ['type' => 'int', 'required' => true]
    ],
    'returns' => ['id' => 'int', 'message' => 'string']
]);

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();
$entryId = (int)($body['entryId'] ?? 0);

if (!$entryId) {
    APIHelper::error("Entry ID to duplicate is required");
}

try {
    $newId = Entries::Duplicate($entryId, $user['id']);
    APIHelper::success(['id' => $newId, 'message' => 'Entry duplicated successfully']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}