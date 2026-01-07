<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Deposit;
use App\Models\User;
use App\Models\FundFlow;
use App\Models\Transaction;
use App\Models\PaymentChannel;

class BotController extends Controller
{
    /**
     * Telegram Webhook 入口
     */
    public function webhook(Request $request)
    {
        $update = $request->all();

        // 调试日志 (上线稳定后可关闭)
        Log::info('TG Webhook:', $update);

        // 1. 处理预支付检查 (必须在 10秒内回复)
        if (isset($update['pre_checkout_query'])) {
            $queryId = $update['pre_checkout_query']['id'];
            $this->answerPreCheckoutQuery($queryId, true);
            return response('ok');
        }

        // 2. 处理支付成功通知
        if (isset($update['message']['successful_payment'])) {
            $payment = $update['message']['successful_payment'];

            // 获取我们在发起支付时传过去的 payload (订单号)
            $orderNo = $payment['invoice_payload'];

            // 获取支付的星星数量
            $totalAmount = $payment['total_amount'];

            $this->handlePaymentSuccess($orderNo, $totalAmount);
            return response('ok');
        }

        return response('ok');
    }

    /**
     * 回复 Telegram: 允许支付
     */
    private function answerPreCheckoutQuery($queryId, $ok)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
//        $baseUrl = "https://api.telegram.org";
        $baseUrl = "https://api.telegram-proxy.org";
        Http::post("{$baseUrl}/bot{$token}/answerPreCheckoutQuery", [
            'pre_checkout_query_id' => $queryId,
            'ok' => $ok
        ]);
    }

    /**
     * 处理加币逻辑 (事务)
     */
    private function handlePaymentSuccess($orderNo, $starsAmount)
    {
        try {
            DB::transaction(function () use ($orderNo, $starsAmount) {
                // 1. 锁订单
                $deposit = Deposit::where('order_no', $orderNo)->lockForUpdate()->first();

                // 如果订单不存在或已处理，直接退出
                if (!$deposit || $deposit->status !== 0) {
                    Log::warning("Stars回调: 订单 {$orderNo} 无效或已处理");
                    return;
                }

                // 2. 锁用户
                $user = User::where('id', $deposit->user_id)->lockForUpdate()->first();

                // 3. 获取汇率 (从数据库读取 stars 通道的配置)
                $channel = PaymentChannel::where('slug', 'stars')->first();
                // 默认 10 (如果没有配置)
                $rate = $channel ? $channel->exchange_rate : 10;

                // 计算实际积分
                $addBalance = $starsAmount * $rate;

                // 4. 更新订单
                $deposit->status = 1;
                $deposit->save();

                // 5. 更新余额
                $beforeBalance = $user->balance;
                $user->increment('balance', $addBalance);
                $afterBalance = $user->balance;

                // 6. 写入资金流水 (FundFlow)
                FundFlow::create([
                    'user_id' => $user->id,
                    'type' => 1, // 充值
                    'amount' => $addBalance,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'order_no' => $orderNo,
                    'remark' => "Stars充值: {$starsAmount} (汇率: {$rate})"
                ]);

                // 7. 写入积分流水 (Transaction)
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 1,
                    'amount' => $addBalance,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'reference_id' => $orderNo,
                    'remark' => "星星充值"
                ]);
            });

            Log::info("Stars充值成功: 单号 {$orderNo}, 金额 {$starsAmount}");

        } catch (\Exception $e) {
            Log::error("Stars回调处理失败: " . $e->getMessage());
        }
    }
}
