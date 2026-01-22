<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'GET',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Retrieves log files list and metadata.',
    'params' => [
        'start' => ['type' => 'string', 'required' => false, 'desc' => 'Filter start date'],
        'end' => ['type' => 'string', 'required' => false, 'desc' => 'Filter end date']
    ],
    'returns' => ['array of log objects']
]);

Auth::check(['ADMIN', 'EDITOR']);

$params = $router->getBody();
$start = $params['start'] ?? null;
$end = $params['end'] ?? null;

$data = Logs::get($start, $end);

APIHelper::success($data);
