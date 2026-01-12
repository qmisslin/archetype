<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Users;

// Public route, no auth check.

$body = $router->getBody();

// Validate required credentials
if (empty($body['email']) || empty($body['password'])) {
    APIHelper::error('Email and password are required');
}

try {
    // Authenticate user and issue a session token
    $data = Users::Login($body['email'], $body['password']);
    APIHelper::success($data);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}
