<?php

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $this */

// TODO: Add Auth check (ADMIN/EDITOR) here

$params = $this->getBody();
$start = $params['start'] ?? null;
$end = $params['end'] ?? null;

$data = Logs::get($start, $end);

APIHelper::success($data);
