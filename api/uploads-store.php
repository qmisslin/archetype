<?php
require_once __DIR__ . '/../../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

$user = Auth::check(['ADMIN', 'EDITOR']);

// Params come from $_POST for multipart/form-data
$name = $_POST['name'] ?? null;
$folderId = isset($_POST['folderId']) && $_POST['folderId'] !== '' ? (int)$_POST['folderId'] : null;
$access = isset($_POST['access']) ? json_decode($_POST['access'], true) : ['PUBLIC', 'EDITOR', 'ADMIN'];

if (!$name || !isset($_FILES['file'])) {
    APIHelper::error("Name and file are required");
}

if (!is_array($access)) {
    APIHelper::error("Access must be a JSON array of roles");
}

try {
    $id = Uploads::Upload($_FILES['file'], $name, $folderId, $access, $user['id']);
    APIHelper::success(['id' => $id, 'message' => 'File uploaded']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}