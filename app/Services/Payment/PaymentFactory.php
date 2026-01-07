<?php
namespace App\Services\Payment;
use Exception;

class PaymentFactory
{
    public static function create(string $driverName)
    {
        // 拼接类名: App\Services\Payment\Drivers\Manual
        $className = "App\\Services\\Payment\\Drivers\\" . $driverName;

        if (!class_exists($className)) {
            throw new Exception("支付驱动 {$driverName} 未定义");
        }

        return new $className();
    }
}
