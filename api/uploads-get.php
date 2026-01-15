<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\Auth;
use Archetype\Core\APIHelper;
use Archetype\Core\Uploads;
use Archetype\Core\Env;

// Public access allowed, but check internal role permissions
$user = Auth::user(); 
$role = $user['role'] ?? 'PUBLIC';

$fileId = $_GET['fileId'] ?? null;
if (!$fileId) APIHelper::error("FileId is required", 400);

$file = Uploads::Get((int)$fileId);

if (!$file) {
    APIHelper::error("File not found", 404);
}

// Access Check
if (!in_array($role, $file['access'])) {
    APIHelper::error("Forbidden", 403);
}

// Serve physical file
if ($file['filepath']) {
    $path = Env::getUploadsPath() . $file['filepath'];
    if (file_exists($path)) {
        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . $file['name'] . '"');
        readfile($path);
        exit;
    } else {
        APIHelper::error("Physical file missing", 404);
    }
} else {
    // If it's a folder, just return JSON info
    APIHelper::success($file);
}