<?php

require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Users;

APIHelper::document([
    'method' => 'POST',
    'role' => 'PUBLIC',
    'description' => 'Bootstraps the first Admin user if none exists.',
    'params' => [],
    'returns' => ['message' => 'string']
]);

// Public route, no auth check.

try {
    // Attempts to create the initial admin user if missing and sends an activation link.
    Users::CreateAdminUser();

    // Always return success to avoid leaking whether the admin exists or not.
    // Critical failures are handled by the global handler or by APIHelper::error().
    APIHelper::success(['message' => 'Admin bootstrap executed. Check system emails/logs.']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}
