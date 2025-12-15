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
// The public front (index.php) primarily serves the API
$router->dispatch('api');

// If the request was not an API call (e.g. /),
// This is where you would place the application's non-API-related rendering logic.
// For a headless CMS, this might just serve a basic HTML shell or be empty.
// For now, we'll confirm that non-API requests are handled.
// Note: In a pure API context, this file might just exist to dispatch.
APIHelper::success(['message' => 'Archetype API is running. Use /api/... routes to access resources.']);

// Optional: Fallback for serving the HTML front for public visitors if needed
// echo file_get_contents('public-html/index.html');