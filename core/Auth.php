<?php

namespace Archetype\Core;

/**
 * Auth class
 * Helper to manage authentication and authorization (RBAC).
 */
class Auth
{
    /**
     * Extracts and validates the Bearer token.
     */
    public static function user(): ?array
    {
        // Extract Header
        $headers = getallheaders();
        // Handle case sensitivity and server specifics
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }
        $token = $matches[1];

        // Verify in DB
        $db = Database::get();
        // Note: We exclude 'PASSWORD_RESET' roles here to prevent using reset tokens for API access
        $stmt = $db->prepare("SELECT userId, role FROM TOKENS WHERE token = ? AND expiration_timestamp > ? AND role != 'PASSWORD_RESET'");
        $stmt->execute([$token, time()]);
        $data = $stmt->fetch();

        if (!$data) return null;

        return [
            'id' => (int)$data['userId'],
            'role' => $data['role'],
            'token' => $token
        ];
    }

    /**
     * Middleware check: Stops execution if unauthorized.
     * @param array $allowedRoles List of allowed roles (e.g. ['ADMIN']). Empty = any authenticated user.
     */
    public static function check(array $allowedRoles = []): array
    {
        $user = self::user();

        if (!$user) {
            APIHelper::error("Unauthorized: Invalid or expired token", 401);
        }

        if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
            APIHelper::error("Forbidden: Insufficient permissions", 403);
        }

        return $user;
    }
}