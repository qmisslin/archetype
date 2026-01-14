<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;

$body = $router->getBody();
$schemeId = (int)($body['schemeId'] ?? 0);
$outdated = (bool)($body['outdated'] ?? false);
$search = $body['search'] ?? null;

if (!$schemeId || !is_array($search)) {
    APIHelper::error("Scheme ID and search AST are required");
}

try {
    $results = Entries::Search($schemeId, $outdated, $search);
    APIHelper::success($results);
} catch (\Throwable $e) {
    APIHelper::error("Search error: " . $e->getMessage());
}