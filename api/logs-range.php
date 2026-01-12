<?php
require_once __DIR__ . '/../core/Boot.php';

use Archetype\Core\APIHelper;
use Archetype\Core\Logs;
use Archetype\Core\Auth;

Auth::check(['ADMIN', 'EDITOR']);

$range = Logs::range();

APIHelper::success($range);
