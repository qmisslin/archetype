<?php

// Load Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

use Archetype\Core\Env;
use Archetype\Core\Router;
use Archetype\Core\APIHelper;

// 1. Load Environment Variables
Env::Init();

// 2. Initialize Router
$router = new Router();

// 3. Dispatch API calls (e.g. /api/...)
// The admin front (admin.php) primarily serves the API
$router->dispatch('api');

APIHelper::success(['message' => 'Archetype Admin API is running. Use /api/... routes to access resources.']);

// Optional: Fallback for serving the HTML front for admin users if needed
// echo file_get_contents('admin-html/index.html');