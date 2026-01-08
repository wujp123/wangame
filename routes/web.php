<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

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

Route::get('/test-db-save', function () {
    try {
        // 尝试手动创建一条数据
        $user = User::create([
            'name' => 'DB Tester',
            'tg_id' => 123456789, // 测试写入这个关键字段
            'username' => 'test_user',
            'password' => bcrypt('123456'),
        ]);

        return "✅ 写入成功！新用户 ID: " . $user->id . "<br>请去数据库 users 表看看有没有这条数据。";
    } catch (\Exception $e) {
        // 把具体错误打印出来
        return "❌ 写入失败！错误信息：<br>" . $e->getMessage();
    }
});

// 1. 首页 (访问域名根目录)
Route::get('/', function () {
    // 这里改成 'game'，对应 resources/views/game.blade.php
    return view('game');
});

// 2. 支付回调页 (NOWPayments 跳转回来的地址)
Route::get('/wallet', function () {
    // 同样加载游戏主界面，因为你的弹窗逻辑写在游戏界面里
    return view('game');
});
