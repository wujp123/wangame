<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Connection;
use Doctrine\DBAL\DriverManager;

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
        // 1. 设置默认字符串长度
        Schema::defaultStringLength(191);

        // 2. 修复 Laravel 11 移除 Doctrine 方法导致 laravel-admin 报错的问题
        try {
            // 补丁 A: 检查 Doctrine 是否可用
            Connection::macro('isDoctrineAvailable', function () {
                return class_exists(\Doctrine\DBAL\Connection::class);
            });

            // 补丁 B: 重新实现 getDoctrineConnection (关键修改：显式传递账号密码)
            Connection::macro('getDoctrineConnection', function () {
                // 获取 Laravel 当前连接的配置信息 (host, username, password 等)
                $config = $this->getConfig();

                $connectionParams = [
                    'pdo'      => $this->getPdo(), // 复用现有连接
                    'dbname'   => $config['database'],
                    'user'     => $config['username'], // <--- 必须显式传递，否则报错 Access denied
                    'password' => $config['password'], // <--- 必须显式传递
                    'host'     => $config['host'] ?? '127.0.0.1',
                    'port'     => $config['port'] ?? 3306,
                    'driver'   => 'pdo_' . ($config['driver'] ?? 'mysql'), // 映射驱动名
                ];

                return DriverManager::getConnection($connectionParams);
            });

            // 补丁 C: 重新实现 getDoctrineSchemaManager
            Connection::macro('getDoctrineSchemaManager', function () {
                return $this->getDoctrineConnection()->createSchemaManager();
            });

            // 补丁 D: 重新实现 getDoctrineColumn
            Connection::macro('getDoctrineColumn', function ($table, $column) {
                return $this->getDoctrineSchemaManager()->listTableDetails($table)->getColumn($column);
            });

        } catch (\Exception $e) {
            // 忽略重复定义错误
        }
    }
}
