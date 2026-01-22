<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Entries;

APIHelper::document([
    'method' => 'GET',
    'role' => 'PUBLIC',
    'description' => 'Lists entries for a scheme with optional filtering.',
    'params' => [
        'schemeId' => ['type' => 'int', 'required' => true],
        'outdated' => ['type' => 'bool', 'required' => false, 'default' => false]
    ],
    'returns' => ['array of entries']
]);

$body = $router->getBody();
$schemeId = (int)($body['schemeId'] ?? 0);
$outdated = (bool)($body['outdated'] ?? false);

if (!$schemeId) {
    APIHelper::error("Scheme ID is required");
}

$entries = Entries::List($schemeId, $outdated);
APIHelper::success($entries);