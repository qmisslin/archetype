<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

if (empty($body['name'])) APIHelper::error("Name is required");
$access = $body['access'] ?? ['PUBLIC', 'EDITOR', 'ADMIN'];
$folderId = isset($body['folderId']) && $body['folderId'] !== '' ? (int)$body['folderId'] : null;

try {
    $id = Uploads::CreateFolder($body['name'], $folderId, $access, $user['id']);
    APIHelper::success(['id' => $id, 'message' => 'Folder created']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}