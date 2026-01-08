<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Deposit;
use App\Models\User;

class WebhookController extends Controller
{
    public function handleNowPayments(Request $request)
    {
        // 1. 获取回调头部的签名
        $receivedSig = $request->header('x-nowpayments-sig');
        $content = $request->getContent(); // 获取原始 body

        // 2. 验证签名 (安全校验)
        // 必须配置 NOWPAYMENTS_IPN_SECRET
        $secret = config('services.nowpayments.ipn_secret');

        if ($secret) {
            $calcSig = hash_hmac('sha512', $content, $secret);
            if ($receivedSig !== $calcSig) {
                Log::warning("NOWPayments 签名验证失败");
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }

        $data = $request->all();
        Log::info("NOWPayments Webhook Data:", $data);

        /*
         * 状态说明:
         * waiting - 等待付款
         * confirming - 链上确认中
         * confirmed - 确认中 (可以算作到账，视金额大小而定)
         * sending - 发送资金中
         * finished - 资金已到达你的钱包 (最终成功)
         * failed - 失败
         */

        $status = $data['payment_status'];
        $orderNo = $data['order_id']; // 我们之前传的 order_id

        // 3. 查找订单
        $deposit = Deposit::where('order_no', $orderNo)->first();
        if (!$deposit) {
            return response()->json(['message' => 'Order not found']);
        }

        // 4. 判断状态并加款
        // 通常 'finished' 是最安全的，'confirmed' 也可以接受
        if ($deposit->status == 0 && in_array($status, ['finished', 'confirmed'])) {

            // 实际上收到的金额 (因为可能有汇率波动或少付)
            // NOWPayments 会返回 pay_amount (支付币种数量) 和 price_amount (原始法币数量)
            // 这里简单处理，认为支付成功就是全额到账

            $deposit->update([
                'status' => 1, // 已支付
                'transaction_id' => $data['payment_id'] ?? null, // 记录对方的流水号
                'updated_at' => now()
            ]);

            // 给用户加积分
            $user = User::find($deposit->user_id);
            if ($user) {
                $user->increment('credits', $deposit->amount);
            }

            Log::info("订单 {$orderNo} 充值成功 (NOWPayments)");
        }

        return response()->json(['status' => 'ok']);
    }
}
