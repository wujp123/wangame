<?php

namespace App\Services\Payment;

interface PaymentDriverInterface
{
    /**
     * 发起支付
     * @param string $orderNo 订单号
     * @param float $amount 金额
     * @param array $config 配置数组
     * @return array
     */
    public function initiate(string $orderNo, float $amount, array $config): array;
}
