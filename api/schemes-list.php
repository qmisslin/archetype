<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'GET',
    'role' => 'ADMIN',
    'description' => 'Lists all available schemas.',
    'params' => [],
    'returns' => ['array of schemas']
]);

Auth::check(['ADMIN']);
APIHelper::success(Schemes::List());