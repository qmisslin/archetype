<?php
require_once __DIR__ . '/../../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $router */

// TODO: Add Auth check (ADMIN) here

$params = $router->getBody();
$filename = $params['filename'] ?? '';

if (Logs::remove($filename)) {
    APIHelper::success(['message' => 'Log file deleted']);
} else {
    APIHelper::error('Could not delete file', 500);
}
