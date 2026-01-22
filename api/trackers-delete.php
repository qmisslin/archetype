<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Trackers;

APIHelper::document([
    'method' => 'DELETE',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Deletes a tracker.',
    'params' => [
        'trackerId' => ['type' => 'int', 'required' => true]
    ],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

$id = isset($body['trackerId']) ? (int)$body['trackerId'] : 0;

if (!$id) {
    APIHelper::error("Tracker ID is required");
}

try {
    Trackers::Remove($id);
    APIHelper::success(['message' => 'Tracker deleted']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}