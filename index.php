<?php

// Automatic class loading (Composer)
require_once __DIR__ . '/vendor/autoload.php';

use Archetype\Core\Env;
use Archetype\Core\Router;
use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

// Environment initialization
try {
    Env::Init();
} catch (\Throwable $e) {
    APIHelper::error('Environment Error: ' . $e->getMessage(), 500);
}

// Global Middleware (Logs & Timer)
// Capture everything that happens from this point
$startTime = microtime(true);
ob_start();

// Extract TID from query string if exists
$tid = null;
if (!empty($_SERVER['REQUEST_URI'])) {
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    if ($query) {
        parse_str($query, $params);
        if (isset($params['tid']) && is_string($params['tid']) && $params['tid'] !== '') {
            $tid = $params['tid'];
        }
    }
}

register_shutdown_function(function () use ($startTime, $tid) {
    $duration = (int)((microtime(true) - $startTime) * 1000);
    $status = http_response_code();
    $bytes = ob_get_length() ?: 0; // Response size
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    $path = $_SERVER['REQUEST_URI'] ?? '/';

    // Log the request if the Logs class is available
    if (class_exists(Logs::class)) {
        Logs::logRequest($method, $path, $status, $duration, $bytes, $tid);
    }
    
    // Send the output buffer to the browser
    if (ob_get_length()) {
        ob_end_flush();
    }
});

// Global fatal error handling
set_exception_handler(function (\Throwable $e) {
    Logs::error('system', 'Uncaught exception', $e);
    ob_clean();
    APIHelper::error("Internal Server Error", 500);
});

// Routing
// This is where the magic happens: delegation to the Router
$router = new Router();
$router->dispatch();