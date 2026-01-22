<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN',
    'description' => 'Cleans up database entries for missing log files.',
    'params' => [],
    'returns' => ['message' => 'string']
]);

Auth::check(['ADMIN']);

$count = Logs::purge();

APIHelper::success(['message' => "Purged $count stats entries"]);
