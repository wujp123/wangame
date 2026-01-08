<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Deposit;
use App\Models\Withdrawal;
use App\Models\FundFlow;
use App\Models\Transaction;
use App\Models\PaymentChannel;
use App\Services\Payment\PaymentService; // 引入支付工厂

class PaymentController extends Controller
{
    /**
     * 0. 获取可用支付通道列表
     * 前端弹窗加载时调用，根据后台配置动态渲染按钮
     */
    public function channels()
    {
        $channels = PaymentChannel::where('is_active', true)
            ->select(['name', 'slug', 'min_amount', 'max_amount', 'exchange_rate'])
            ->get();

        return response()->json([
            'code' => 200,
            'data' => $channels
        ]);
    }

    /**
     * 1. 发起充值请求
     * 支持所有已配置的通道 (Stars, USDT, Alipay 等)
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string' // 前端传来的 slug, 如 'stars', 'usdt_trc20'
        ]);

        $user = $request->user();

        // 【开发后门】如果未登录且是本地环境，方便测试 (生产环境请删除)
//        if (!$user && app()->isLocal()) {
//            $user = User::find(1);
//        }
        if (!$user) {
            return response()->json(['code' => 401, 'message' => '未登录'], 401);
        }

        // 1. 获取通道配置
        $channel = PaymentChannel::where('slug', $request->input('method'))
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return response()->json(['code' => 400, 'message' => '支付通道不存在或已关闭'], 400);
        }

        // 2. 验证金额范围
        if ($request->amount < $channel->min_amount || $request->amount > $channel->max_amount) {
            return response()->json(['code' => 400, 'message' => "金额限制: {$channel->min_amount} - {$channel->max_amount}"], 400);
        }

        // 生成订单号
        $orderNo = 'DEP' . date('YmdHis') . rand(1000, 9999);

        // 3. 创建本地订单
        // 注意：这里记录的是用户支付的数量（比如 100 USDT 或 100 Stars），不是最终积分
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'order_no' => $orderNo,
            'amount' => $request->amount,
            'status' => 0, // 待支付
            'payment_method' => $channel->slug // 记录 slug
        ]);

        try {
            // 4. ★★★ 核心：使用工厂模式调用对应的支付驱动 ★★★
            // PaymentService 会根据 $channel->driver 实例化 Manual 或 TelegramStars
            $driver = PaymentService::createDriver($channel->driver);

            // 发起支付，返回给前端需要的数据 (Url, Qrcode 等)
            // ★★★ 必须按这个新顺序调用 ★★★
            // 参数1: 订单号
            // 参数2: 金额
            // 参数3: 配置
            $payResult = $driver->initiate($orderNo, $request->amount, $channel->config ?? []);

            return response()->json([
                'code' => 200,
                'data' => $payResult
            ]);

        } catch (\Exception $e) {
            Log::error("支付发起失败: " . $e->getMessage());
            return response()->json(['code' => 500, 'message' => '支付服务异常: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. 充值回调 / 模拟回调
     * 用于前端“模拟支付成功”按钮，或者第三方支付的通用回调
     * (Stars 支付的 webhook 通常由 BotController 单独处理，但也可能走这里，视架构而定)
     */
    public function notify(Request $request)
    {
        $orderNo = $request->input('order_no');

        Log::info("收到充值回调: " . json_encode($request->all()));

        if (!$orderNo) return response()->json(['status' => 'fail'], 400);

        try {
            DB::transaction(function () use ($orderNo) {
                // 1. 锁订单
                $deposit = Deposit::where('order_no', $orderNo)->lockForUpdate()->first();
                if (!$deposit || $deposit->status !== 0) return;

                // 2. 锁用户
                $user = User::where('id', $deposit->user_id)->lockForUpdate()->first();

                // 3. ★★★ 获取汇率 ★★★
                $channel = PaymentChannel::where('slug', $deposit->payment_method)
                    ->orWhere('name', $deposit->payment_method)
                    ->first();

                // 默认汇率 1:1，如果通道有配置则使用配置 (例如 10)
                $rate = $channel ? $channel->exchange_rate : 1.00;

                // 计算实际到账积分 = 支付金额 * 汇率
                $actualPoints = $deposit->amount * $rate;

                // 4. 更新订单状态
                $deposit->status = 1;
                $deposit->save();

                // 5. 更新余额
                $beforeBalance = $user->balance;
                $user->increment('balance', $actualPoints);
                $afterBalance = $user->balance;

                // 6. ★★★ 写入资金流水 (FundFlow - 财务看) ★★★
                FundFlow::create([
                    'user_id' => $user->id,
                    'type'    => 1, // 充值
                    'amount'  => $actualPoints,
                    'before_balance' => $beforeBalance,
                    'after_balance'  => $afterBalance,
                    'order_no' => $deposit->order_no,
                    'remark'   => "充值: {$deposit->amount} (汇率: {$rate})"
                ]);

                // 7. ★★★ 写入积分流水 (Transaction - 用户账单) ★★★
                Transaction::create([
                    'user_id' => $user->id,
                    'type'    => 1, // 充值
                    'amount'  => $actualPoints,
                    'before_balance' => $beforeBalance,
                    'after_balance'  => $afterBalance,
                    'reference_id' => $deposit->order_no,
                    'remark'   => "在线充值"
                ]);
            });

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error("回调处理失败: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * 3. 申请提现
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount'  => 'required|numeric|min:10',
            'address' => 'required|string|min:5',
        ]);

        $user = $request->user();

        // 【开发后门】
        if (!$user && app()->isLocal()) $user = User::find(1);
        if (!$user) return response()->json(['code' => 401, 'message' => '未登录'], 401);

        $amount = $request->amount;

        // 预检查余额
        if ($user->balance < $amount) {
            return response()->json(['code' => 400, 'message' => '余额不足'], 400);
        }

        try {
            DB::transaction(function () use ($user, $amount, $request) {
                // 1. 锁用户
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                if ($user->balance < $amount) throw new \Exception("余额不足");

                // 2. 扣除余额 (冻结)
                $beforeBalance = $user->balance;
                $user->decrement('balance', $amount);
                $afterBalance = $user->balance;

                // 3. ★★★ 写入资金流水 ★★★
                FundFlow::create([
                    'user_id' => $user->id,
                    'type'    => 2, // 提现
                    'amount'  => -$amount,
                    'before_balance' => $beforeBalance,
                    'after_balance'  => $afterBalance,
                    'order_no' => null,
                    'remark'   => "申请提现: {$amount}"
                ]);

                // 4. ★★★ 写入积分流水 (让用户看到扣款) ★★★
                Transaction::create([
                    'user_id' => $user->id,
                    'type'    => 2,
                    'amount'  => -$amount,
                    'before_balance' => $beforeBalance,
                    'after_balance'  => $afterBalance,
                    'remark'   => "提现申请"
                ]);

                // 5. 创建提现工单
                Withdrawal::create([
                    'user_id' => $user->id,
                    'amount'  => $amount,
                    'actual_amount' => $amount, // 这里可扣手续费
                    'fee'     => 0,
                    'status'  => 0, // 待审核
                    'to_address' => $request->address
                ]);
            });

            return response()->json(['code' => 200, 'message' => '提现申请已提交，请等待审核']);

        } catch (\Exception $e) {
            return response()->json(['code' => 400, 'message' => $e->getMessage()], 400);
        }
    }
}
