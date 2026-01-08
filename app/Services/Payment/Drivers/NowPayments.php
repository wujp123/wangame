<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NowPayments implements PaymentDriverInterface
{
    public function initiate(string $orderNo, float $amount, array $config): array
    {
        // 1. 获取配置
        $apiKey = $config['api_key'] ?? config('services.nowpayments.api_key');
        $environment = $config['env'] ?? config('services.nowpayments.env');

        if (empty($apiKey)) {
            throw new Exception("NOWPayments API Key 未配置");
        }

        // 2. 确定 API 地址
        $baseUrl = ($environment === 'sandbox')
            ? 'https://api-sandbox.nowpayments.io/v1'
            : 'https://api.nowpayments.io/v1';

        // 3. 构造请求参数
        // 文档: https://documenter.getpostman.com/view/7979558/T1lszJra#create-invoice
        $payload = [
            'price_amount'     => $amount,        // 用户要充值的法币金额 (例如 100)
            'price_currency'   => 'USD',          // 法币单位 (根据你的系统调整，如 CNY, USD)
            'order_id'         => $orderNo,       // 你的订单号
            'order_description'=> "充值订单: {$orderNo}",
            'ipn_callback_url' => route('webhook.nowpayments'), // 下面会定义这个路由
            'success_url'      => url('/wallet?status=success'), // 支付成功后跳回你的网页
            'cancel_url'       => url('/wallet?status=cancel'),
        ];

        try {
            Log::info("NOWPayments Request:", $payload);

            // 4. 发起请求
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$baseUrl}/invoice", $payload);

            Log::info("NOWPayments Response:", $response->json() ?? []);

            if ($response->successful() && isset($response['invoice_url'])) {
                // 5. 返回 URL 给前端
                // 前端代码里有 if (data.type === 'url') tg.openLink(...)
                return [
                    'type' => 'url',
                    'url'  => $response['invoice_url'],
                    'order_no' => $orderNo
                ];
            }

            throw new Exception("创建订单失败: " . ($response['message'] ?? $response->body()));

        } catch (Exception $e) {
            Log::error("NOWPayments Error: " . $e->getMessage());
            throw $e;
        }
    }
}
