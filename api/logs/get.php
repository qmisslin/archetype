<?php
require_once __DIR__ . '/../../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $router */

// TODO: Add Auth check (ADMIN/EDITOR) here

$params = $router->getBody();
$start = $params['start'] ?? null;
$end = $params['end'] ?? null;

$data = Logs::get($start, $end);

APIHelper::success($data);
