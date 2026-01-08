<?php

namespace App\Services\Payment;

use Exception;

class PaymentService
{
    /**
     * ★★★ 核心配置：驱动注册表 ★★★
     * Key: 数据库存的字段 (slug)
     * Value: [class => 类路径, label => 后台显示的名称]
     */
    protected static $drivers = [
        'TelegramStars' => [
            'class' => \App\Services\Payment\Drivers\TelegramStars::class,
            'label' => 'Telegram Stars (官方支付)'
        ],
        'Manual' => [
            'class' => \App\Services\Payment\Drivers\Manual::class,
            'label' => '人工/静态地址 (Manual)'
        ],
        'UsdtApi' => [
            'class' => \App\Services\Payment\Drivers\UsdtApi::class,
            'label' => 'USDT API接口 (UsdtApi)'
        ],
        'NowPayments' => [
            'class' => \App\Services\Payment\Drivers\NowPayments::class,
            'label' => '加密货币 (USDT/BTC/ETH)'
        ],
        // 以后加支付宝，直接在这里加一行即可
        // 'Alipay' => ['class' => ..., 'label' => '支付宝'],
    ];

    /**
     * 获取给后台 Select/Grid 用的选项数组
     * 返回格式: ['Manual' => '人工...', 'TelegramStars' => 'Stars...']
     */
    public static function getOptions(): array
    {
        // 提取 label 组成新数组
        return array_map(function ($item) {
            return $item['label'];
        }, self::$drivers);
    }

    /**
     * 工厂方法：创建驱动实例
     */
    public static function createDriver(string $driverName)
    {
        if (!isset(self::$drivers[$driverName])) {
            throw new Exception("未找到支付驱动: {$driverName}");
        }

        $className = self::$drivers[$driverName]['class'];

        if (!class_exists($className)) {
            throw new Exception("驱动类不存在: {$className}");
        }

        return new $className();
    }
}
