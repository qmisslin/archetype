<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN',
    'description' => 'Adds a new field definition to a scheme.',
    'params' => [
        'schemeID' => ['type' => 'int', 'required' => true],
        'field' => ['type' => 'json/array', 'required' => true, 'desc' => 'Field definition']
    ],
    'returns' => ['message' => 'string']
]);

$user = Auth::check(['ADMIN']);
$body = $router->getBody();
$id = (int)($body['schemeID'] ?? 0);

try {
    Schemes::AddField($id, $body['field'], $user['id']);
    APIHelper::success(['message' => 'Field added']);
} catch (\Throwable $e) {
    APIHelper::error($e->getMessage());
}