<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

Auth::check(['ADMIN', 'EDITOR']);

$params = $router->getBody();

if (empty($params['id']) || empty($params['filename'])) {
    APIHelper::error('Missing log ID or filename');
}

Logs::compute((int)$params['id'], $params['filename']);

APIHelper::success(['message' => 'Stats computed']);
