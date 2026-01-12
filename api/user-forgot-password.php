<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Users;

// Public route, no auth check.

$body = $router->getBody();

// Validate required input
if (empty($body['email'])) {
    APIHelper::error('Email is required');
}

try {
    // Initiate password reset flow
    Users::ForgotPassword($body['email']);

    // Always return a generic success message to prevent email enumeration
    APIHelper::success(['message' => 'If this email exists, a reset link has been sent.']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}
