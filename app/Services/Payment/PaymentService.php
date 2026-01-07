<?php

namespace App\Services\Payment;

use App\Models\PaymentChannel;
use Exception;

class PaymentService
{
    public static function createDriver(string $driverName): PaymentDriverInterface
    {
        // 映射驱动名称到类文件
        $drivers = [
            'TelegramStars' => \App\Services\Payment\Drivers\TelegramStars::class,
            'Manual'        => \App\Services\Payment\Drivers\Manual::class,
            // 'EpysPay'    => \App\Services\Payment\Drivers\EpysPay::class,
        ];

        if (!isset($drivers[$driverName])) {
            throw new Exception("未找到支付驱动: {$driverName}");
        }

        return new $drivers[$driverName]();
    }
}
