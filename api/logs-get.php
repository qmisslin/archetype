<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

Auth::check(['ADMIN', 'EDITOR']);

$params = $router->getBody();
$start = $params['start'] ?? null;
$end = $params['end'] ?? null;

$data = Logs::get($start, $end);

APIHelper::success($data);
