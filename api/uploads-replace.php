<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN, EDITOR',
    'description' => 'Replaces the physical file content of an existing entry.',
    'params' => [
        'fileId' => ['type' => 'int', 'required' => true],
        'file' => ['type' => 'file', 'required' => true]
    ],
    'returns' => ['message' => 'string']
]);

$user = Auth::check(['ADMIN', 'EDITOR']);

$fileId = $_POST['fileId'] ?? null;
if (!$fileId || !isset($_FILES['file'])) {
    APIHelper::error("FileId and file are required");
}

try {
    Uploads::Replace((int)$fileId, $_FILES['file'], $user['id']);
    APIHelper::success(['message' => 'File replaced']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage(), 500);
}