<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Trackers;

APIHelper::document([
    'method' => 'GET',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Lists all trackers.',
    'params' => [],
    'returns' => ['array of trackers']
]);

Auth::check(['ADMIN', 'EDITOR']);

try {
    $list = Trackers::List();
    APIHelper::success($list);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}