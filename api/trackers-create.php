<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Trackers;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Creates a new tracker.',
    'params' => [
        'name' => ['type' => 'string', 'required' => true],
        'description' => ['type' => 'string', 'required' => false]
    ],
    'returns' => ['id' => 'int', 'message' => 'string']
]);

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

if (empty($body['name'])) {
    APIHelper::error("Name is required");
}

$description = $body['description'] ?? null;

try {
    $id = Trackers::Create($body['name'], $description, $user['id']);
    APIHelper::success(['id' => $id, 'message' => 'Tracker created']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}