<?php

namespace Archetype\Core;

use PDO;
use PDOException;

/**
 * Database class
 * Manages the database connection and schema initialization with cross-driver support.
 */
class Database
{
    private static ?PDO $pdo = null;

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
            
            self::createTables();
        } catch (PDOException $e) {
            APIHelper::error("Database connection failed: " . $e->getMessage(), 500);
        }
    }

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
        $type = $_ENV['DB_TYPE'] ?? 'SQLITE';
        
        // Handle syntax differences for Primary Keys 
        $pk = ($type === 'SQLITE') 
            ? "INTEGER PRIMARY KEY AUTOINCREMENT" 
            : "INT AUTO_INCREMENT PRIMARY KEY";

        $queries = [
            "CREATE TABLE IF NOT EXISTS LOGS (
                id $pk,
                logfile TEXT NOT NULL,
                timestamp INTEGER NOT NULL,
                stats TEXT DEFAULT NULL,
                modification_date TEXT DEFAULT NULL
            )",
            "CREATE TABLE IF NOT EXISTS USERS (
                id $pk,
                email VARCHAR(255) NOT NULL UNIQUE,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                role TEXT CHECK(role IN ('PUBLIC', 'EDITOR', 'ADMIN')) NOT NULL DEFAULT 'PUBLIC',
                enable INTEGER DEFAULT 1,
                creation_timestamp INTEGER NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS TOKENS (
                token VARCHAR(255) PRIMARY KEY,
                expiration_timestamp INTEGER NOT NULL,
                userId INTEGER NOT NULL,
                role TEXT NOT NULL,
                creation_timestamp INTEGER NOT NULL,
                ip TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS ATTEMPTS (
                ip VARCHAR(255) NOT NULL,
                user_email VARCHAR(255) NOT NULL,
                attempt_number INTEGER DEFAULT 0,
                type TEXT CHECK(type IN ('LOGIN', 'RESET_PASSWORD')) NOT NULL,
                timestamp INTEGER NOT NULL,
                PRIMARY KEY (ip, type)
            )",
            "CREATE TABLE IF NOT EXISTS SCHEMES (
                id $pk,
                version INTEGER DEFAULT 1,
                name TEXT NOT NULL,
                fields TEXT NOT NULL,
                creation_timestamp INTEGER NOT NULL,
                modification_timestamp INTEGER NOT NULL,
                last_modification_userId INTEGER NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS ENTRIES (
                id $pk,
                schemeId INTEGER NOT NULL,
                schemeVersion INTEGER NOT NULL,
                data TEXT NOT NULL,
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