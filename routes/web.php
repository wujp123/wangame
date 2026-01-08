<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// --- 临时维护路由 ---
Route::get('/clear-all', function() {
    try {
        // 清除配置缓存 (最重要)
        Artisan::call('config:clear');

        // 清除应用缓存
        Artisan::call('cache:clear');

        // 清除视图/路由缓存
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return "缓存清理成功！(Config, Cache, View, Route cleared)";
    } catch (\Exception $e) {
        // 如果报错，可能是权限问题，尝试方法二
        return "清理失败: " . $e->getMessage();
    }
});
