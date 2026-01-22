<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;

APIHelper::document([
    'method' => 'POST',
    'role' => 'PUBLIC',
    'description' => 'Searches entries using an AST JSON query.',
    'params' => [
        'schemeId' => ['type' => 'int', 'required' => true],
        'search' => ['type' => 'json/array', 'required' => true, 'desc' => 'AST Search Query'],
        'outdated' => ['type' => 'bool', 'required' => false]
    ],
    'returns' => ['array of entries']
]);

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