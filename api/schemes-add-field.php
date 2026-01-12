<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

$user = Auth::check(['ADMIN']);
$body = $router->getBody();
$id = (int)($body['schemeID'] ?? 0);

try {
    Schemes::AddField($id, $body['field'], $user['id']);
    APIHelper::success(['message' => 'Field added']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}