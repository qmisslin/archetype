<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Forces stats computation for a specific log file.',
    'params' => [
        'id' => ['type' => 'int', 'required' => true],
        'filename' => ['type' => 'string', 'required' => true]
    ],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN', 'EDITOR']);

$params = $router->getBody();

if (empty($params['id']) || empty($params['filename'])) {
    APIHelper::error('Missing log ID or filename');
}

Logs::compute((int)$params['id'], $params['filename']);

APIHelper::success(['message' => 'Stats computed']);
