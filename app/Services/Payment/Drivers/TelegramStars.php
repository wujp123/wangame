<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentDriverInterface;
use Illuminate\Support\Facades\Http;

class TelegramStars implements PaymentDriverInterface
{
    public function initiate(string $orderNo, float $amount, array $config): array
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');

        // 调用 TG API
        $response = Http::post("https://api.telegram.org/bot{$botToken}/createInvoiceLink", [
            'title'       => '充值积分',
            'description' => "充值数量: " . (int)$amount,
            'payload'     => $orderNo,
            'provider_token' => "",
            'currency'    => "XTR",
            'prices'      => [['label' => 'Stars', 'amount' => (int)$amount]],
        ]);

        if ($response->successful()) {
            return [
                'type' => 'stars',
                'url'  => $response->json('result')
            ];
        }

        throw new \Exception("TG API Error: " . $response->body());
    }
}
