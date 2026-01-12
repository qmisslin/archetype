<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

$user = Auth::check(['ADMIN']);
$body = $router->getBody();
$id = (int)($body['schemeID'] ?? 0);
$key = $body['key'] ?? '';
$index = isset($body['index']) ? (int)$body['index'] : -1;

if (!$key || $index < 0) APIHelper::error("Key and a valid positive index are required");

try {
    Schemes::IndexField($id, $key, $index, $user['id']);
    APIHelper::success(['message' => 'Field reordered']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}