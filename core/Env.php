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
        $rootPath = dirname(__DIR__, 1);

        // Load .env file
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // SMTP variables are always required
        $dotenv->required([
            'SMTP_USER',
            'SMTP_PASS',
            'SMTP_HOST',
            'SMTP_PORT',
            'SMTP_SECURE',
        ])->notEmpty();

        // Validate SMTP_SECURE allowed values
        $dotenv->required('SMTP_SECURE')
            ->allowedValues(['tls', 'ssl', 'none']);

        // DB_TYPE is always required and must be one of the supported types
        $dotenv->required('DB_TYPE')
            ->notEmpty()
            ->allowedValues(['SQLITE', 'MYSQL', 'POSTGRES']);

        $dbType = $_ENV['DB_TYPE'] ?? null;

        // Conditional DB validation based on DB_TYPE
        if ($dbType === 'SQLITE') {
            // For SQLite, only the file path is required
            $dotenv->required('DB_FILEPATH')->notEmpty();
        } elseif ($dbType === 'MYSQL' || $dbType === 'POSTGRES') {
            // For server-based DBs, connection details are required
            $dotenv->required([
                'DB_HOST',
                'DB_PORT',
                'DB_NAME',
                'DB_USER',
                'DB_PASS',
            ])->notEmpty();
        }
    }
}
