<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

// Public route, no auth check.

// Retrieve data
$body = $router->getBody();
$type = $body['type'] ?? 'INF';

// Basic validation
$allowedTypes = ['INF', 'WRN', 'ERR'];
// If the type is not allowed (e.g. REQ), force it to INF
if (!in_array($type, $allowedTypes)) {
    $type = 'INF';
}

// Content cleanup
// IMPORTANT: Remove 'type' from the body to prevent it from overriding the validated value during merge
unset($body['type']);
$content = $body;

// Write log
Logs::message($type, $content);

APIHelper::success(['message' => 'Log saved']);
