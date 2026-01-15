<?php
require_once __DIR__ . '/../../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

if (empty($body['fileId']) || empty($body['name'])) {
    APIHelper::error("FileId and name are required");
}
$access = $body['access'] ?? ['PUBLIC', 'EDITOR', 'ADMIN'];

try {
    Uploads::Edit((int)$body['fileId'], $body['name'], $access, $user['id']);
    APIHelper::success(['message' => 'Item updated']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}