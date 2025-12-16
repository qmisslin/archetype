<?php

namespace Archetype\Core;

use PDO;

/**
 * Logs class
 * Handles file-based logging and database tracking of log files.
 */
class Logs
{
    // private const MAX_FILE_SIZE = 3 * 1024 * 1024; // 3 MB (prod limit)
    private const MAX_FILE_SIZE = 10 * 1024; // 10 KB (test limit)

    private const LOG_DIR = 'data/logs/';

    private static ?string $currentLogFile = null;
    private static ?int $currentLogId = null;

    /**
     * Retrieves the current log file. Rotates if necessary.
     */
    public static function getCurrent(): array
    {
        // 1. Ensure directory exists
        $root = dirname(__DIR__, 1);
        $dir = $root . '/' . self::LOG_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // 2. Get most recent entry from DB
        $db = Database::get();
        $stmt = $db->query("SELECT id, logfile FROM LOGS ORDER BY timestamp DESC LIMIT 1");
        $lastLog = $stmt->fetch();

        // 3. Check file existence and size
        if ($lastLog) {
            $filePath = $dir . $lastLog['logfile'];
            if (file_exists($filePath)) {
                if (filesize($filePath) < self::MAX_FILE_SIZE) {
                    self::$currentLogId = (int)$lastLog['id'];
                    self::$currentLogFile = $lastLog['logfile'];
                    return ['id' => self::$currentLogId, 'file' => self::$currentLogFile];
                }
            }
        }

        // 4. Create new if missing or full
        return self::rotate();
    }

    /**
     * Creates a new log file and entry in the database.
     */
    private static function rotate(): array
    {
        $db = Database::get();
        $timestamp = time();
        
        // CORRECTION 1 : Cast explicite en (int) avant le modulo
        $ms = (int)(microtime(true) * 1000);
        $milliseconds = $ms % 1000;
        
        // date + time + milliseconds.log
        $filename = date('Y-m-d_H-i-s_', $timestamp) . sprintf('%03d', $milliseconds) . '.log';
        
        // Create physical file
        $root = dirname(__DIR__, 1);
        touch($root . '/' . self::LOG_DIR . $filename);

        // Insert into DB
        $stmt = $db->prepare("INSERT INTO LOGS (logfile, timestamp) VALUES (?, ?)");
        $stmt->execute([$filename, $timestamp]);
        
        self::$currentLogId = (int)$db->lastInsertId();
        self::$currentLogFile = $filename;

        return ['id' => self::$currentLogId, 'file' => $filename];
    }

    /**
     * Appends a message to the current log file.
     * @param string $type The log type (REQ, INF, WRN, ERR)
     * @param array $data Content of the log
     */
    public static function message(string $type, array $data): void
    {
        $current = self::getCurrent();
        $root = dirname(__DIR__, 1);
        $path = $root . '/' . self::LOG_DIR . $current['file'];

        // Standard JSONL structure
        $entry = array_merge([
            'ts' => date('c'), // ISO 8601
            'type' => $type,
        ], $data);

        file_put_contents($path, json_encode($entry) . PHP_EOL, FILE_APPEND);
    }

    /**
     * Helper to log an HTTP Request (Middleware usage).
     */
    public static function logRequest(string $method, string $path, int $status, int $durationMs, int $bytes, ?string $tid = null): void
    {
        $data = [
            'level' => 'info',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'method' => $method,
            // MODIFICATION: Clean path (strip query params)
            'path' => parse_url($path, PHP_URL_PATH),
            'status' => $status,
            'duration_ms' => $durationMs,
            'tid' => $tid, // Tracking ID
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'bytes' => $bytes
        ];

        self::message('REQ', $data);
    }

    /**
     * Helper for generic info messages.
     */
    public static function info(string $category, string $message, ?string $tid = null): void
    {
        self::message('INF', [
            'level' => 'info',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'message' => $message,
            'category' => $category,
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
            'tid' => $tid
        ]);
    }

    /**
     * Helper for exceptions/errors.
     * CORRECTION 2 : Ajout du '?' devant \Throwable pour autoriser le null explicitement.
     */
    public static function error(string $category, string $message, ?\Throwable $e = null, ?string $tid = null): void
    {
        $data = [
            'level' => 'error',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'message' => $message,
            'category' => $category,
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH),
            'tid' => $tid,
        ];

        if ($e) {
            $data['exception_class'] = get_class($e);
            $data['exception_message'] = $e->getMessage();
            $data['stack_hash'] = substr(md5($e->getTraceAsString()), 0, 8);
        }

        self::message('ERR', $data);
    }
}