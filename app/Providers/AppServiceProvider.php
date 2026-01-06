<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 添加下面这一行判断，强制生产环境或所有环境使用 HTTPS
        if (app()->environment('production') || true) { // 这里的 || true 表示强制开启，适合你现在的情况
            URL::forceScheme('https');
        }
    }
}
