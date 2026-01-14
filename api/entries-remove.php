<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;
use Archetype\Core\Auth;

Auth::check(['ADMIN', 'EDITOR']);
$body = $router->getBody();
$entryId = (int)($body['entryId'] ?? 0);

if (!$entryId) {
    APIHelper::error("Entry ID is required");
}

try {
    Entries::Remove($entryId);
    APIHelper::success(['message' => 'Entry removed']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}