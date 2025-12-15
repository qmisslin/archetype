<?php

namespace Archetype\Core;

use Dotenv\Dotenv;

/**
 * Env class
 * Loads and validates environment variables from the .env file.
 */
class Env
{
    /**
     * Loads environment variables using Dotenv and makes them available
     * via $_ENV and getenv().
     */
    public static function Init(): void
    {
        // Define the project root directory
        $rootPath = dirname(__DIR__, 2);

        // Load .env file
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // --- Basic Validation (as per specs) ---
        // Validate that core variables for DB and Email are set.

        // DB variables (check existence, not validity yet)
        $dotenv->required([
            'DB_TYPE',
            'DB_HOST',
            'DB_PORT',
            'DB_NAME',
            'DB_USER',
            'DB_PASS',
        ])->notEmpty();

        // Email variables
        $dotenv->required([
            'EMAIL_USER',
            'EMAIL_PASS',
        ])->notEmpty();

        // Note: Specific value validation (e.g., DB_TYPE is SQLITE)
        // should be handled in the Database class Init() for better context.
    }
}