<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'GET',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Retrieves the min and max timestamps available in logs.',
    'params' => [],
    'returns' => ['min' => 'int', 'max' => 'int']
]);

Auth::check(['ADMIN', 'EDITOR']);

$range = Logs::range();

APIHelper::success($range);
