<?php

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;

/** @var Archetype\Core\Router $this */

// TODO: Add Auth check (ADMIN) here

$params = $this->getBody();
$filename = $params['filename'] ?? '';

if (Logs::remove($filename)) {
    APIHelper::success(['message' => 'Log file deleted']);
} else {
    APIHelper::error('Could not delete file', 500);
}
