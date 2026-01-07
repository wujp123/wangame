<?php

namespace App\Services\Payment\Drivers;

use App\Services\Payment\PaymentDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UsdtApi implements PaymentDriverInterface
{
    /**
     * 发起支付 (必须与接口定义完全一致)
     * @param string $orderNo 订单号
     * @param float $amount 金额
     * @param array $config 配置参数 (后台配置的 api_url, app_id, secret 等)
     * @return array
     */
    public function initiate(string $orderNo, float $amount, array $config): array
    {
        // 1. 从配置中读取参数
        // (需要在后台支付通道配置里填入这些 Key)
        $apiUrl = $config['api_url'] ?? '';
        $merchantId = $config['merchant_id'] ?? '';
        $secretKey = $config['secret_key'] ?? '';

        if (empty($apiUrl)) {
            throw new \Exception("USDT API 配置不完整: 缺少 api_url");
        }

        // 2. 构造请求参数 (这里仅为示例，具体看对接文档)
        $params = [
            'mch_id' => $merchantId,
            'out_trade_no' => $orderNo,
            'amount' => $amount,
            'currency' => 'USDT',
            'notify_url' => url('/api/notify'), // 你的回调地址
            'return_url' => url('/game.html'),
        ];

        // 3. 生成签名 (示例)
        // $params['sign'] = $this->generateSign($params, $secretKey);

        try {
            // 4. 发送请求给第三方支付网关
            // $response = Http::post($apiUrl, $params);

            // 假设第三方返回了 json: { "code": 1, "data": { "pay_url": "https://..." } }
            // $result = $response->json();

            // if ($result['code'] !== 1) {
            //     throw new \Exception("上游报错: " . ($result['msg'] ?? '未知错误'));
            // }

            // ★★★ 模拟返回 (为了让你先跑通流程) ★★★
            // 真实对接时，请把上面注释的代码打开，并根据文档修改
            return [
                'type' => 'url', // 告诉前端这是一个跳转链接
                'url'  => 'https://google.com/search?q=模拟支付跳转' . $orderNo // 这里填真实的收银台地址
            ];

        } catch (\Exception $e) {
            Log::error("USDT API 请求失败: " . $e->getMessage());
            throw new \Exception("支付请求失败，请稍后重试");
        }
    }

    // 签名辅助函数示例
    private function generateSign($params, $secret)
    {
        ksort($params);
        $str = http_build_query($params) . '&key=' . $secret;
        return strtoupper(md5($str));
    }
}
