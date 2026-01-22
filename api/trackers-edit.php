<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Trackers;

APIHelper::document([
    'method' => 'PATCH',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Updates an existing tracker.',
    'params' => [
        'trackerId' => ['type' => 'int', 'required' => true],
        'name' => ['type' => 'string', 'required' => true],
        'description' => ['type' => 'string', 'required' => false]
    ],
    'returns' => ['message' => 'string']
]);

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

$id = isset($body['trackerId']) ? (int)$body['trackerId'] : 0;

if (!$id || empty($body['name'])) {
    APIHelper::error("Tracker ID and Name are required");
}

$description = $body['description'] ?? null;

try {
    Trackers::Edit($id, $body['name'], $description, $user['id']);
    APIHelper::success(['message' => 'Tracker updated']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}