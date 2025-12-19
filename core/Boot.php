<?php

namespace Archetype\Core;

// Bootstrap: root path and autoloader
$root = dirname(__DIR__, 1);
require_once $root . '/vendor/autoload.php';

use Throwable;

// Environment initialization
try {
    Env::Init();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Environment error: ' . $e->getMessage()]);
    exit;
}

// Default API headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

// Global middleware: timing and logging
$startTime = microtime(true);
ob_start();

register_shutdown_function(function () use ($startTime) {
    $duration = (int)((microtime(true) - $startTime) * 1000);
    $status = http_response_code();
    $bytes = ob_get_length() ?: 0;
    $tid = $_GET['tid'] ?? null;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    $path = $_SERVER['REQUEST_URI'] ?? '/';

    if (class_exists(Logs::class)) {
        Logs::logRequest($method, $path, $status, $duration, $bytes, $tid);
    }

    if (ob_get_length()) {
        ob_end_flush();
    }
});

// Global exception handler
set_exception_handler(function (Throwable $e) {
    Logs::error('system', 'Uncaught exception', $e);
    ob_clean();
    APIHelper::error('Internal Server Error', 500);
});

// Router initialization (available to all API files)
$router = new Router();
