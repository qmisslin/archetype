<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Users;

// Public route, no auth check.

$body = $router->getBody();

// Validate required inputs for password reset
if (empty($body['token']) || empty($body['email']) || empty($body['password'])) {
    APIHelper::error('Token, email, and new password are required');
}

try {
    // Complete password reset using a one-time token
    Users::ChangePassword($body['token'], $body['email'], $body['password']);
    APIHelper::success(['message' => 'Password updated successfully.']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}
