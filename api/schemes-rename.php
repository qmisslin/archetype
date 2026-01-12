<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

$user = Auth::check(['ADMIN']);
$body = $router->getBody();
$id = (int)($body['schemeID'] ?? 0);
$name = $body['name'] ?? '';

if (!$name) APIHelper::error("Name is required");

try {
    Schemes::Rename($id, $name, $user['id']);
    APIHelper::success(['message' => 'Scheme renamed successfully']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}