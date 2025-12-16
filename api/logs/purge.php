<?php

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $this */

// TODO: Add Auth check (ADMIN) here

$count = Logs::purge();

APIHelper::success(['message' => "Purged $count stats entries"]);
