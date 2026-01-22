<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Users;

APIHelper::document([
    'method' => 'POST',
    'role' => 'PUBLIC',
    'description' => 'Authenticates a user and returns a session token.',
    'params' => [
        'email' => ['type' => 'string', 'required' => true],
        'password' => ['type' => 'string', 'required' => true]
    ],
    'returns' => [
        'token' => 'string',
        'user' => ['id' => 'int', 'role' => 'string', 'name' => 'string']
    ]
]);

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
