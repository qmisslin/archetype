<?php

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $this */

// TODO: Add Auth check (ADMIN/EDITOR) here

$range = Logs::range();

APIHelper::success($range);
