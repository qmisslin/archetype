<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Creates a new entry based on a scheme.',
    'params' => [
        'schemeId' => ['type' => 'int', 'required' => true],
        'data' => ['type' => 'json/array', 'required' => true]
    ],
    'returns' => ['id' => 'int', 'message' => 'string']
]);

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

$schemeId = (int)($body['schemeId'] ?? 0);
$data = $body['data'] ?? null;

if (!$schemeId || !$data) {
    APIHelper::error("Scheme ID and data are required");
}

try {
    $id = Entries::Create($schemeId, (array)$data, $user['id']);
    APIHelper::success(['id' => $id, 'message' => 'Entry created successfully']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}