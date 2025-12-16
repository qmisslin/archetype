<?php

namespace Archetype\Core;

use PDO;

/**
 * Logs class
 * Handles file-based logging, database tracking, and statistics computation.
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
        // Ensure directory exists
        $root = dirname(__DIR__, 1);
        $dir = $root . '/' . self::LOG_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Get most recent entry from DB
        $db = Database::get();
        $stmt = $db->query("SELECT id, logfile FROM LOGS ORDER BY timestamp DESC LIMIT 1");
        $lastLog = $stmt->fetch();

        // Check file existence and size
        if ($lastLog) {
            $filePath = $dir . $lastLog['logfile'];
            if (file_exists($filePath)) {
                if (filesize($filePath) < self::MAX_FILE_SIZE) {
                    self::$currentLogId = (int)$lastLog['id'];
                    self::$currentLogFile = $lastLog['logfile'];
                    return ['id' => self::$currentLogId, 'file' => self::$currentLogFile];
                } else {
                    // File is full: Compute stats for the old file before rotating
                    self::compute((int)$lastLog['id'], $lastLog['logfile']);
                }
            }
        }

        // Create new if missing or full
        return self::rotate();
    }

    /**
     * Creates a new log file and entry in the database.
     */
    private static function rotate(): array
    {
        $db = Database::get();
        $timestamp = time();
        
        // Cast explicite en (int) avant le modulo
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
            'ts' => date('c'),
            'type' => $type,
        ], $data);

        // Using FILE_APPEND | LOCK_EX to avoid race conditions
        file_put_contents($path, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Helper to log an HTTP Request (Middleware usage).
     */
    public static function logRequest(string $method, string $path, int $status, int $durationMs, int $bytes, ?string $tid = null): void
    {
        self::message('REQ', [
            'level' => 'info',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'method' => $method,
            'path' => parse_url($path, PHP_URL_PATH),
            'status' => $status,
            'duration_ms' => $durationMs,
            'tid' => $tid,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'bytes' => $bytes
        ]);
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

    /**
     * Reads a log file and computes statistics.
     * Updates the 'stats' column in the database.
     */
    public static function compute(int $logId, string $filename): void
    {
        $root = dirname(__DIR__, 1);
        $filePath = $root . '/' . self::LOG_DIR . $filename;

        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return;
        }

        // Initialize Stats Structure
        $stats = [
            'meta' => ['generated_at' => date('c'), 'rows_processed' => 0],
            'period' => ['from' => null, 'to' => null],
            'global' => [
                'requests' => 0, 'unique_clients' => 0, 'errors' => 0, 'warnings' => 0, 'infos' => 0,
                'total_bytes' => 0, 'total_duration_ms' => 0, 'avg_duration_ms' => 0
            ],
            'requests_by_status' => [],
            'requests_by_path' => [],
            'tid_stats' => [],
            'timeline_per_day' => []
        ];

        $uniqueIps = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry) continue;

            $stats['meta']['rows_processed']++;
            
            // Period
            if ($stats['period']['from'] === null) $stats['period']['from'] = $entry['ts'];
            $stats['period']['to'] = $entry['ts'];

            // Global Counts
            $type = $entry['type'] ?? 'UNK';
            $ip = $entry['ip'] ?? 'unknown';
            $uniqueIps[$ip] = true;

            if ($type === 'REQ') $stats['global']['requests']++;
            if ($type === 'ERR') $stats['global']['errors']++;
            if ($type === 'WRN') $stats['global']['warnings']++;
            if ($type === 'INF') $stats['global']['infos']++;

            // Timeline (per minute resolution for precision)
            $timeKey = substr($entry['ts'], 0, 16); // 2025-01-15T09:12
            if (!isset($stats['timeline_per_day'][$timeKey])) {
                $stats['timeline_per_day'][$timeKey] = ['requests' => 0, 'messages' => 0, 'errors' => 0, 'warnings' => 0];
            }

            if ($type === 'REQ') {
                $stats['timeline_per_day'][$timeKey]['requests']++;
                
                $status = $entry['status'] ?? 0;
                $bytes = $entry['bytes'] ?? 0;
                $duration = $entry['duration_ms'] ?? 0;
                $path = $entry['path'] ?? '/unknown';

                $stats['global']['total_bytes'] += $bytes;
                $stats['global']['total_duration_ms'] += $duration;

                // By Status
                if (!isset($stats['requests_by_status'][$status])) $stats['requests_by_status'][$status] = 0;
                $stats['requests_by_status'][$status]++;

                // By Path
                if (!isset($stats['requests_by_path'][$path])) {
                    $stats['requests_by_path'][$path] = [
                        'requests' => 0, 'total_duration' => 0, 'avg_duration_ms' => 0,
                        'status_counts' => [], 'bytes' => 0, 'warnings' => 0, 'errors' => 0
                    ];
                }
                $pStats = &$stats['requests_by_path'][$path];
                $pStats['requests']++;
                $pStats['total_duration'] += $duration;
                $pStats['bytes'] += $bytes;
                if (!isset($pStats['status_counts'][$status])) $pStats['status_counts'][$status] = 0;
                $pStats['status_counts'][$status]++;
                
                if ($status >= 400 && $status < 500) $pStats['warnings']++; // HTTP 4xx often considered warnings/client errors
                if ($status >= 500) $pStats['errors']++;

            } else {
                // INF, WRN, ERR
                $stats['timeline_per_day'][$timeKey]['messages']++;
                if ($type === 'ERR') $stats['timeline_per_day'][$timeKey]['errors']++;
                if ($type === 'WRN') $stats['timeline_per_day'][$timeKey]['warnings']++;
            }

            // TID Stats
            if (!empty($entry['tid'])) {
                $tid = $entry['tid'];
                if (!isset($stats['tid_stats'][$tid])) {
                    $stats['tid_stats'][$tid] = ['request_count' => 0, 'ips' => []];
                }
                $stats['tid_stats'][$tid]['request_count']++;
                $stats['tid_stats'][$tid]['ips'][$ip] = true;
            }
        }

        // Post-process calculations
        $stats['global']['unique_clients'] = count($uniqueIps);
        if ($stats['global']['requests'] > 0) {
            $stats['global']['avg_duration_ms'] = round($stats['global']['total_duration_ms'] / $stats['global']['requests'], 2);
        }

        foreach ($stats['requests_by_path'] as &$pStats) {
            if ($pStats['requests'] > 0) {
                $pStats['avg_duration_ms'] = round($pStats['total_duration'] / $pStats['requests'], 2);
            }
            unset($pStats['total_duration']); // Cleanup intermediate value
        }
        
        // Clean TID IPs to just counts
        foreach ($stats['tid_stats'] as &$tStats) {
            $tStats['unique_ips'] = count($tStats['ips']);
            unset($tStats['ips']);
        }

        // Save to DB
        $db = Database::get();
        $stmt = $db->prepare("UPDATE LOGS SET stats = ?, modification_date = ? WHERE id = ?");
        $stmt->execute([json_encode($stats), date('Y-m-d H:i:s'), $logId]);
    }

    /**
     * Get min and max timestamps from logs.
     */
    public static function range(): array
    {
        $db = Database::get();
        $stmt = $db->query("SELECT MIN(timestamp) as min, MAX(timestamp) as max FROM LOGS");
        return $stmt->fetch() ?: ['min' => 0, 'max' => 0];
    }

    /**
     * List logs with optional date filtering.
     */
    public static function get(?string $start = null, ?string $end = null): array
    {
        $db = Database::get();
        $sql = "SELECT * FROM LOGS";
        $params = [];

        if ($start && $end) {
            $sql .= " WHERE timestamp BETWEEN ? AND ?";
            $params = [strtotime($start), strtotime($end)];
        }
        $sql .= " ORDER BY timestamp DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Check physical file existence
        $root = dirname(__DIR__, 1) . '/' . self::LOG_DIR;
        foreach ($logs as &$log) {
            $log['file_exists'] = file_exists($root . $log['logfile']);
            if ($log['stats']) {
                $log['stats'] = json_decode($log['stats'], true);
            }
        }

        return $logs;
    }

    /**
     * Get full path for a log file (for download).
     */
    public static function getFilePath(string $filename): ?string
    {
        // Security: Prevent directory traversal
        $filename = basename($filename);
        $path = dirname(__DIR__, 1) . '/' . self::LOG_DIR . $filename;
        return file_exists($path) ? $path : null;
    }

    /**
     * Remove a log file and its DB entry.
     */
    public static function remove(string $filename): bool
    {
        $filename = basename($filename);
        $path = dirname(__DIR__, 1) . '/' . self::LOG_DIR . $filename;
        
        if (file_exists($path)) {
            unlink($path);
        }

        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM LOGS WHERE logfile = ?");
        return $stmt->execute([$filename]);
    }

    /**
     * Purge stats for missing files.
     */
    public static function purge(): int
    {
        $db = Database::get();
        $logs = $db->query("SELECT id, logfile FROM LOGS")->fetchAll();
        $root = dirname(__DIR__, 1) . '/' . self::LOG_DIR;
        $count = 0;

        foreach ($logs as $log) {
            if (!file_exists($root . $log['logfile'])) {
                // If file is missing, we clear the stats (or we could delete the entry)
                // Specs say "delete stats related to a log file that has been deleted"
                $stmt = $db->prepare("UPDATE LOGS SET stats = NULL WHERE id = ?");
                $stmt->execute([$log['id']]);
                $count++;
            }
        }
        return $count;
    }
}