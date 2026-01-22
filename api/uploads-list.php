<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

APIHelper::document([
    'method' => 'GET',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Lists files and folders inside a directory.',
    'params' => [
        'folderId' => ['type' => 'int', 'required' => false, 'desc' => 'Null for root']
    ],
    'returns' => ['array of files/folders']
]);

Auth::check(['ADMIN', 'EDITOR']);

$folderId = isset($_GET['folderId']) && $_GET['folderId'] !== '' ? (int)$_GET['folderId'] : null;

try {
    $list = Uploads::List($folderId);
    APIHelper::success($list);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}