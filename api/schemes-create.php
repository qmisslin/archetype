<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

APIHelper::document([
    'method' => 'POST',
    'role' => 'ADMIN',
    'description' => 'Creates a new empty scheme.',
    'params' => [
        'name' => ['type' => 'string', 'required' => true]
    ],
    'returns' => ['id' => 'int']
]);

$user = Auth::check(['ADMIN']);
$name = $router->getBody()['name'] ?? '';

if (!$name) APIHelper::error("Name is required");
$id = Schemes::Create($name, $user['id']);
APIHelper::success(['id' => $id]);