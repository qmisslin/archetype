<?php

namespace Archetype\Core;

/**
 * Users service.
 * Handles user bootstrap, authentication, and password reset flows.
 */
class Users
{
    /**
     * Bootstraps the initial admin user using SMTP_USER as the admin email.
     * The account is created with a locked password and activated via a reset link.
     */
    public static function CreateAdminUser(): void
    {
        // Admin email is derived from SMTP configuration
        $email = $_ENV['SMTP_USER'] ?? null;
        if (!$email) {
            Logs::info('auth', 'Admin bootstrap skipped: SMTP_USER is not set.');
            return;
        }

        // Base URL required to generate activation links
        $appUrl = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
        if ($appUrl === '') {
            Logs::info('auth', 'Admin bootstrap skipped: APP_URL is not set.');
            return;
        }

        $db = Database::get();

        // Abort if admin user already exists
        $stmt = $db->prepare('SELECT id FROM USERS WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return;
        }

        // Create user with an unknown password to force reset
        $garbagePass = bin2hex(random_bytes(16));
        $hash = password_hash($garbagePass, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO USERS (email, password, name, role, enable, creation_timestamp) VALUES (?, ?, 'System Admin', 'ADMIN', 1, ?)");
        $stmt->execute([$email, $hash, time()]);

        $userId = $db->lastInsertId();

        // One-time password reset token (1h validity)
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 3600;
        $ip = '127.0.0.1'; // System-generated action

        $stmt = $db->prepare('INSERT INTO TOKENS (token, expiration_timestamp, userId, role, creation_timestamp, ip) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$token, $expiry, $userId, 'PASSWORD_RESET', time(), $ip]);

        // Activation link sent to the admin email
        $link = $appUrl . $_ENV['RESET_PASSWORD_PATH'] . '?token=' . urlencode($token);

        try {
            Email::send(
                [$email],
                'Welcome to Archetype',
                'text',
                "Welcome Administrator.\n\nTo activate your account and set your password, use the link below:\n\n{$link}\n\n(Valid for 1 hour)"
            );
            Logs::info('auth', "Admin user created: {$email}. Activation link sent.");
        } catch (\Throwable $e) {
            Logs::error('auth', 'Admin user created, but email failed.', $e);
        }
    }

    /**
     * Authenticates a user and returns a session token.
     */
    public static function Login(string $email, string $password): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $type = 'LOGIN';

        // Brute-force protection
        $wait = Attempts::check($ip, $type);
        if ($wait === -1) {
            Logs::message('WRN', ['msg' => 'Blocked login attempt', 'ip' => $ip, 'email' => $email]);
            APIHelper::error('Access blocked due to too many failed attempts.', 403);
        }
        if ($wait > 0) {
            APIHelper::error("Too many attempts. Please wait {$wait}s.", 429);
        }

        $db = Database::get();
        $stmt = $db->prepare('SELECT * FROM USERS WHERE email = ? AND enable = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            Attempts::delete($ip, $type);

            // Create session token (24h)
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (24 * 3600);

            $stmt = $db->prepare('INSERT INTO TOKENS (token, expiration_timestamp, userId, role, creation_timestamp, ip) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$token, $expiry, $user['id'], $user['role'], time(), $ip]);

            Logs::info('auth', "User authenticated: {$email}");

            return [
                'token' => $token,
                'role' => $user['role'],
                'name' => $user['name'],
            ];
        }

        Attempts::register($ip, $type, $email);
        Logs::message('WRN', ['msg' => 'Login failed', 'email' => $email, 'ip' => $ip]);
        APIHelper::error('Invalid email or password', 401);
        return [];
    }

    /**
     * Starts a password reset request.
     * Always returns success to prevent email enumeration.
     */
    public static function ForgotPassword(string $email): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $type = 'RESET_PASSWORD';

        // Rate limiting to prevent abuse
        $wait = Attempts::check($ip, $type);
        if ($wait !== 0) {
            APIHelper::error('Too many attempts. Please wait.', 429);
        }

        $appUrl = rtrim((string)($_ENV['APP_URL'] ?? ''), '/');
        if ($appUrl === '') {
            Logs::error('auth', 'Password reset aborted: APP_URL is not set.');
            APIHelper::error('Server configuration error', 500);
        }

        $db = Database::get();
        $stmt = $db->prepare('SELECT id FROM USERS WHERE email = ? AND enable = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate one-time reset token
            $token = bin2hex(random_bytes(32));
            $expiry = time() + 3600;

            // Ensure only one active reset token per user
            $del = $db->prepare("DELETE FROM TOKENS WHERE userId = ? AND role = 'PASSWORD_RESET'");
            $del->execute([$user['id']]);

            $stmt = $db->prepare("INSERT INTO TOKENS (token, expiration_timestamp, userId, role, creation_timestamp, ip) VALUES (?, ?, ?, 'PASSWORD_RESET', ?, ?)");
            $stmt->execute([$token, $expiry, $user['id'], time(), $ip]);

            $link = $appUrl . $_ENV['RESET_PASSWORD_PATH'] . '?token=' . urlencode($token);

            Email::send(
                [$email],
                'Reset your password',
                'text',
                "You requested a password reset.\n\nUse the link below to set a new password:\n\n{$link}\n\n(Valid for 1 hour)"
            );

            Attempts::register($ip, $type, $email);
        } else {
            // Fake workload to mitigate timing attacks
            usleep(random_int(100000, 300000));
        }

        Logs::info('auth', "Password reset requested for: {$email}");
    }

    /**
     * Completes a password reset using a one-time token.
     */
    public static function ChangePassword(string $token, string $email, string $newPassword): void
    {
        $db = Database::get();

        // Validate reset token and ownership
        $stmt = $db->prepare("
            SELECT t.userId, u.email
            FROM TOKENS t
            JOIN USERS u ON t.userId = u.id
            WHERE t.token = ?
            AND t.expiration_timestamp > ?
            AND t.role = 'PASSWORD_RESET'
        ");
        $stmt->execute([$token, time()]);
        $data = $stmt->fetch();

        if (!$data || $data['email'] !== $email) {
            APIHelper::error('Invalid or expired token', 400);
        }

        // Update password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $db->prepare('UPDATE USERS SET password = ? WHERE id = ?');
        $upd->execute([$hash, $data['userId']]);

        // Consume token and clear security counters
        $del = $db->prepare('DELETE FROM TOKENS WHERE token = ?');
        $del->execute([$token]);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        Attempts::delete($ip, 'RESET_PASSWORD');
        Attempts::delete($ip, 'LOGIN');

        Logs::info('auth', "Password updated for: {$email}");
    }
}
