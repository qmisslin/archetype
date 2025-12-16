<?php

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $this */

// TODO: Add Auth check (ADMIN) here

$params = $this->getBody();
$filename = $params['filename'] ?? '';

if (!$filename) {
    APIHelper::error('Filename required', 400);
}

$path = Logs::getFilePath($filename);

if ($path && file_exists($path)) {
    // Send raw file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
} else {
    APIHelper::error('File not found', 404);
}
