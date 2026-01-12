<?php

namespace Archetype\Core;

use PDO;
use PDOException;

/**
 * Database class
 * Manages the database connection and schema initialization.
 */
class Database
{
    private static ?PDO $pdo = null;

    /**
     * Connects to the database and creates tables if they do not exist.
     */
    public static function Init(): void
    {
        $type = $_ENV['DB_TYPE'] ?? 'SQLITE';

        try {
            if ($type === 'SQLITE') {
                self::initSqlite();
            } else {
                self::initServerDb();
            }

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Create essential tables
            self::createTables();

        } catch (PDOException $e) {
            APIHelper::error("Database connection failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Get the PDO instance.
     */
    public static function get(): PDO
    {
        if (self::$pdo === null) {
            self::Init();
        }
        return self::$pdo;
    }

    private static function initSqlite(): void
    {
        $fullPath = Env::getDbPath();
        self::$pdo = new PDO('sqlite:' . $fullPath);
    }

    private static function initServerDb(): void
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            strtolower($_ENV['DB_TYPE']),
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        );

        self::$pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
    }

    private static function createTables(): void
    {
        // SQL for SQLite. (MySQL/Postgres adaptation would require specific driver checks if syntax differs significantly, 
        // but standard SQL is used here where possible).
        
        $queries = [
            // LOGS Table
            "CREATE TABLE IF NOT EXISTS LOGS (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                logfile TEXT NOT NULL,
                timestamp INTEGER NOT NULL,
                stats TEXT DEFAULT NULL,
                modification_date TEXT DEFAULT NULL
            )",
            // USERS Table
            "CREATE TABLE IF NOT EXISTS USERS (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT CHECK(role IN ('PUBLIC', 'EDITOR', 'ADMIN')) NOT NULL DEFAULT 'PUBLIC',
                enable INTEGER DEFAULT 1,
                creation_timestamp INTEGER NOT NULL
            )",
            // TOKENS Table
            "CREATE TABLE IF NOT EXISTS TOKENS (
                token TEXT PRIMARY KEY,
                expiration_timestamp INTEGER NOT NULL,
                userId INTEGER NOT NULL,
                role TEXT NOT NULL,
                creation_timestamp INTEGER NOT NULL,
                ip TEXT NOT NULL
            )",
            // ATTEMPTS Table - Required for login logic later
             "CREATE TABLE IF NOT EXISTS ATTEMPTS (
                ip TEXT NOT NULL,
                user_email TEXT NOT NULL,
                attempt_number INTEGER DEFAULT 0,
                type TEXT CHECK(type IN ('LOGIN', 'RESET_PASSWORD')) NOT NULL,
                timestamp INTEGER NOT NULL,
                PRIMARY KEY (ip, type)
            )",
            // SCHEMES Table - Defines data structures
            "CREATE TABLE IF NOT EXISTS SCHEMES (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                version INTEGER DEFAULT 1,
                name TEXT NOT NULL,
                fields TEXT NOT NULL, -- JSON array of fields
                creation_timestamp INTEGER NOT NULL,
                modification_timestamp INTEGER NOT NULL,
                last_modification_userId INTEGER NOT NULL
            )",
            // ENTRIES Table - Stores data entries based on schemes
            "CREATE TABLE IF NOT EXISTS ENTRIES (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                schemeId INTEGER NOT NULL,
                schemeVersion INTEGER NOT NULL,
                data TEXT NOT NULL, -- JSON object
                creation_timestamp INTEGER NOT NULL,
                modification_timestamp INTEGER NOT NULL,
                last_modification_userId INTEGER NOT NULL
            )"
        ];

        foreach ($queries as $sql) {
            self::$pdo->exec($sql);
        }
    }
}