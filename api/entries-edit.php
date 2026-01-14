<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;
use Archetype\Core\Auth;

$user = Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();

$entryId = (int)($body['entryId'] ?? 0);
$data = $body['data'] ?? null;

if (!$entryId || !$data) {
    APIHelper::error("Entry ID and updated data are required");
}

try {
    Entries::Edit($entryId, (array)$data, $user['id']);
    APIHelper::success(['message' => 'Entry updated successfully']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}