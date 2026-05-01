<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = dirname(__DIR__).'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require dirname(__DIR__).'/vendor/autoload.php';

/** @var Application $app */
$app = require_once dirname(__DIR__).'/bootstrap/app.php';

$app->handleRequest(Request::capture());
