<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

Auth::check(['ADMIN']);

$params = $router->getBody();
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
