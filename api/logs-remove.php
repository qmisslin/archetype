<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'DELETE',
    'role' => 'ADMIN',
    'description' => 'Deletes a log file and its database entry.',
    'params' => [
        'filename' => ['type' => 'string', 'required' => true]
    ],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN']);

$params = $router->getBody();
$filename = $params['filename'] ?? '';

if (Logs::remove($filename)) {
    APIHelper::success(['message' => 'Log file deleted']);
} else {
    APIHelper::error('Could not delete file', 500);
}
