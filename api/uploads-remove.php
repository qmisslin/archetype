<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

if (empty($body['fileId'])) APIHelper::error("FileId is required");

try {
    Uploads::Remove((int)$body['fileId']);
    APIHelper::success(['message' => 'Item deleted']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}