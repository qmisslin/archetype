<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

Auth::check(['ADMIN']);

$body = $router->getBody();
$id = (int)($body['schemeID'] ?? 0);

if (!$id) {
    APIHelper::error("Scheme ID is required");
}

try {
    Schemes::Remove($id);
    APIHelper::success(['message' => 'Scheme and related entries deleted']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}