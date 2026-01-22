<?php

namespace Archetype\Core;

use Dotenv\Dotenv;

/**
 * Env class.
 * Loads environment variables and centralizes path management.
 */
class Env
{
    /**
     * Initializes environment variables and validates them.
     */
    public static function Init(): void
    {
        $rootPath = dirname(__DIR__, 1);
        $dotenv = Dotenv::createImmutable($rootPath);
        $dotenv->safeLoad();

        self::validate($dotenv);
    }

    /**
     * Helper to ensure a directory exists and return the absolute path.
     */
    private static function resolveAndPrepare(string $envKey, bool $isFile = false): string
    {
        $rootPath = dirname(__DIR__, 1);
        $rawPath = $_ENV[$envKey] ?? '';
        $cleanPath = ltrim($rawPath, '/');
        $fullPath = $rootPath . '/' . $cleanPath;
        $dirToCreate = $isFile ? dirname($fullPath) : $fullPath;

        if (!is_dir($dirToCreate)) {
            mkdir($dirToCreate, 0775, true);
        }

        return $fullPath;
    }

    public static function getDbPath(): string 
    {
        return self::resolveAndPrepare('DB_PATH', true);
    }

    public static function getLogsPath(): string 
    {
        // On s'assure que le chemin se termine par un slash pour les classes utilisatrices
        return rtrim(self::resolveAndPrepare('LOGS_PATH'), '/') . '/';
    }

    public static function getUploadsPath(): string 
    {
        return rtrim(self::resolveAndPrepare('UPLOADS_PATH'), '/') . '/';
    }

    public static function getLogMaxFileSize(): int
    {
        return isset($_ENV['LOG_MAX_FILE_SIZE']) ? (int)$_ENV['LOG_MAX_FILE_SIZE'] : 3145728;
    }

    private static function validate(Dotenv $dotenv): void
    {
        $dotenv->required(['APP_URL', 'RESET_PASSWORD_PATH'])->notEmpty();

        $dotenv->required(['SMTP_USER', 'SMTP_PASS', 'SMTP_HOST', 'SMTP_PORT', 'SMTP_SECURE'])->notEmpty();
        $dotenv->required('SMTP_SECURE')->allowedValues(['tls', 'ssl', 'none']);

        $dotenv->required('DB_TYPE')->notEmpty()->allowedValues(['SQLITE', 'MYSQL', 'POSTGRES']);

        if (($_ENV['DB_TYPE'] ?? '') === 'SQLITE') {
            $dotenv->required('DB_PATH')->notEmpty();
        } else {
            $dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'])->notEmpty();
            $dotenv->required('DB_PASS');
        }

        $dotenv->required(['LOGS_PATH', 'UPLOADS_PATH'])->notEmpty();
    }
}