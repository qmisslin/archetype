<?php
require_once __DIR__ . '/../core/Boot.php';
use Archetype\Core\APIHelper;
use Archetype\Core\Schemes;
use Archetype\Core\Auth;

Auth::check(['ADMIN']);
$id = (int)($router->getBody()['schemeID'] ?? 0);
$scheme = Schemes::Get($id);

if (!$scheme) APIHelper::error("Scheme not found", 404);
APIHelper::success($scheme);