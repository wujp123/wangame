<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer 自动加载
require __DIR__.'/vendor/autoload.php';

// 启动 Laravel
(require_once __DIR__.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
