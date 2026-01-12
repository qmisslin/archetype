<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

$user = Auth::check(['ADMIN']);
$body = $router->getBody();
$id = (int)($body['schemeID'] ?? 0);
$key = $body['key'] ?? '';
$field = $body['field'] ?? null;

if (!$key || !$field) APIHelper::error("Key and updated field data are required");

try {
    Schemes::UpdateField($id, $key, $field, $user['id']);
    APIHelper::success(['message' => 'Field updated and version managed']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}