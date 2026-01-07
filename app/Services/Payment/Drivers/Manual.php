<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentDriverInterface;

class Manual implements PaymentDriverInterface
{
    public function initiate(string $orderNo, float $amount, array $config): array
    {
        $address = $config['wallet_address'] ?? '请联系客服';

        return [
            'type'     => 'manual',
            'order_no' => $orderNo,
            'amount'   => $amount,
            'address'  => $address,
            'qrcode'   => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $address
        ];
    }
}
