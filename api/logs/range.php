<?php
require_once __DIR__ . '/../../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $router */

// TODO: Add Auth check (ADMIN/EDITOR) here

$range = Logs::range();

APIHelper::success($range);
