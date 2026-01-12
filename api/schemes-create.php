<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

$user = Auth::check(['ADMIN']);
$name = $router->getBody()['name'] ?? '';

if (!$name) APIHelper::error("Name is required");
$id = Schemes::Create($name, $user['id']);
APIHelper::success(['id' => $id]);