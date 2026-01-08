<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // 引入日志
use Exception;

class TelegramStars implements PaymentDriverInterface
{
    public function initiate(string $orderNo, float $amount, array $config): array
    {
        // 1. 优先从 config 获取 token，避免生产环境 env() 返回 null
        // 也可以从 $config 数组（数据库 json 字段）里取，看你业务逻辑
        $botToken = $config['bot_token'] ?? config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');

        if (empty($botToken)) {
            throw new Exception("Telegram Bot Token 未配置");
        }

        // 2. 建议先用官方 API 测试，排除代理问题
        // 如果服务器在国内，这里必须换成你自己搭建的稳定代理
//        $baseUrl = "https://api.telegram.org";
         $baseUrl = "https://odd-moon-5f2c.loerwaldpuotinen600.workers.dev";

        $url = "{$baseUrl}/bot{$botToken}/test/createInvoiceLink";

        // 3. 构造请求参数
        $payload = [
            'title'       => '积分充值',
            'description' => "充值订单: {$orderNo}",
            'payload'     => $orderNo,
            'provider_token' => "", // Stars 必须留空
            'currency'    => "XTR", // Stars 必须是 XTR
            'prices'      => [
                ['label' => 'Stars', 'amount' => (int)$amount] // 必须是整数
            ],
        ];

        // 4. 记录请求日志 (调试用)
        Log::info("TG Stars Request:", ['url' => $url, 'payload' => $payload]);

        try {
            // 发送请求
            $response = Http::post($url, $payload);

            // 记录响应日志
            Log::info("TG Stars Response:", $response->json() ?? []);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['result'])) {
                    return [
                        'type' => 'stars',
                        'url'  => $result['result'] // 返回 https://t.me/$...
                    ];
                }
            }

            // 如果失败，抛出具体错误信息
            throw new Exception("TG API Error: " . ($response->json('description') ?? $response->body()));

        } catch (\Exception $e) {
            Log::error("TG Payment Failed: " . $e->getMessage());
            throw $e;
        }
    }
}
