<?php

namespace Archetype\Core;

use Dotenv\Dotenv;

/**
 * Env class.
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
        $rootPath = dirname(__DIR__, 1);

        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        // Application base URL (used for public links in emails)
        $dotenv->required('APP_URL')->notEmpty();

        // SMTP configuration
        $dotenv->required([
            'SMTP_USER',
            'SMTP_PASS',
            'SMTP_HOST',
            'SMTP_PORT',
            'SMTP_SECURE',
        ])->notEmpty();

        $dotenv->required('SMTP_SECURE')
            ->allowedValues(['tls', 'ssl', 'none']);

        // Database configuration
        $dotenv->required('DB_TYPE')
            ->notEmpty()
            ->allowedValues(['SQLITE', 'MYSQL', 'POSTGRES']);

        $dbType = $_ENV['DB_TYPE'] ?? null;

        if ($dbType === 'SQLITE') {
            $dotenv->required('DB_FILEPATH')->notEmpty();
        } elseif ($dbType === 'MYSQL' || $dbType === 'POSTGRES') {
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
