<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;

APIHelper::document([
    'method' => 'GET',
    'role' => 'PUBLIC',
    'description' => 'Retrieves a single entry by ID.',
    'params' => [
        'entryId' => ['type' => 'int', 'required' => true]
    ],
    'returns' => ['entry object']
]);

$body = $router->getBody();
$entryId = (int)($body['entryId'] ?? 0);

if (!$entryId) {
    APIHelper::error("Entry ID is required");
}

$entry = Entries::GetById($entryId);

if (!$entry) {
    APIHelper::error("Entry not found", 404);
}

APIHelper::success($entry);