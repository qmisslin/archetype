<?php

namespace Archetype\Core;

/**
 * Attempts class
 * Manages brute-force protection logic with progressive delays.
 */
class Attempts
{
    /**
     * Checks if an IP is allowed to perform an action.
     * @return int 0 if allowed, seconds to wait if delayed, -1 if blocked (>= 6 attempts).
     */
    public static function check(string $ip, string $type): int
    {
        $db = Database::get();
        
        // Check Global Block (on any action type)
        // Spec: "A blocked IP remains blocked regardless of the action type"
        $stmt = $db->prepare("SELECT MAX(attempt_number) as max_attempts FROM ATTEMPTS WHERE ip = ?");
        $stmt->execute([$ip]);
        $max = $stmt->fetch()['max_attempts'] ?? 0;

        if ($max >= 6) {
            return -1; // Blocked globally
        }

        // Check Specific Delay for this action
        $stmt = $db->prepare("SELECT * FROM ATTEMPTS WHERE ip = ? AND type = ?");
        $stmt->execute([$ip, $type]);
        $attempt = $stmt->fetch();

        if (!$attempt) return 0;

        $n = (int)$attempt['attempt_number'];
        $ts = (int)$attempt['timestamp'];
        $diff = time() - $ts;

        // Progressive delays based on specs
        $wait = 0;
        if ($n === 3) $wait = 300;   // 5 minutes
        if ($n === 4) $wait = 600;   // 10 minutes
        if ($n === 5) $wait = 3600;  // 60 minutes

        if ($diff < $wait) {
            return $wait - $diff;
        }

        return 0;
    }

    /**
     * Registers a failed attempt.
     */
    public static function register(string $ip, string $type, string $email = ''): void
    {
        $db = Database::get();
        
        // Get current attempts to increment
        $stmt = $db->prepare("SELECT attempt_number FROM ATTEMPTS WHERE ip = ? AND type = ?");
        $stmt->execute([$ip, $type]);
        $curr = $stmt->fetch();
        
        $n = ($curr['attempt_number'] ?? 0) + 1;

        // Use REPLACE INTO to insert or update
        $stmt = $db->prepare("REPLACE INTO ATTEMPTS (ip, user_email, attempt_number, type, timestamp) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$ip, $email, $n, $type, time()]);
    }

    /**
     * Clears attempts after a successful action.
     */
    public static function delete(string $ip, string $type): void
    {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM ATTEMPTS WHERE ip = ? AND type = ?");
        $stmt->execute([$ip, $type]);
    }

    /**
     * List all attempts (Admin usage).
     */
    public static function list(): array
    {
        $db = Database::get();
        return $db->query("SELECT * FROM ATTEMPTS")->fetchAll();
    }
}