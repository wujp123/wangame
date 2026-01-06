<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\GameLog;
use App\Models\Transaction;
// 如果你之前创建了代理返佣事件，请取消下面这行的注释
// use App\Events\UserBetEvent;

class GameController extends Controller
{
    /**
     * 水果 ID 定义 (与前端对应)
     * 1: BAR, 2: 77, 3: 星星, 4: 西瓜,
     * 5: 铃铛, 6: 柠檬, 7: 橘子, 8: 苹果
     */

    // 赔率配置 (ID => 倍率)
    protected $odds = [
        1 => 50,  // Bar
        2 => 40,  // 77
        3 => 30,  // 星星
        4 => 20,  // 西瓜
        5 => 20,  // 铃铛
        6 => 15,  // 柠檬
        7 => 10,  // 橘子
        8 => 5    // 苹果
    ];

    /**
     * 跑马灯布局 (外圈 24 个格子对应的水果 ID)
     * 顺序：从左上角开始 -> 上排(往右) -> 右排(往下) -> 下排(往左) -> 左排(往上)
     * 必须与前端 Pixi.js 的绘制顺序严格一致！
     */
    protected $boardLayout = [
        // 上排 (0-6)
        7, 6, 5, 4, 3, 2, 1,
        // 右排 (7-12)
        1, 8, 8, 7, 6, 5,
        // 下排 (13-18)
        4, 3, 2, 8, 8, 7,
        // 左排 (19-23)
        6, 5, 8, 8, 7
    ];

    /**
     * 获取余额接口
     */
    public function balance(Request $request)
    {
        // 优先获取中间件验证过的用户
        $user = $request->user();

        // 【开发/测试后门】如果没登录，回退到 ID=1
        // 上线生产环境时，建议删除下面这几行
        if (!$user) {
            $user = User::find(1);
        }

        if (!$user) {
            return response()->json(['code' => 404, 'message' => '用户未找到'], 404);
        }

        return response()->json([
            'code' => 200,
            'data' => ['balance' => $user->balance]
        ]);
    }

    /**
     * 核心旋转接口
     */
    public function spin(Request $request)
    {
        // 1. 强制 JSON 响应，防止报错跳转
        if (!$request->wantsJson()) {
            //
        }

        // 2. 验证参数
        // 格式: { "bets": { "8": 100, "4": 20 } }
        $request->validate([
            'bets'   => 'required|array',
            'bets.*' => 'integer|min:0', // 允许压0，但不允许负数
        ]);

        $inputBets = $request->input('bets');

        // 计算总下注额
        $totalBetAmount = array_sum($inputBets);

        if ($totalBetAmount <= 0) {
            return response()->json(['code' => 400, 'message' => '请至少下注一项'], 400);
        }

        // 3. 获取用户
        $user = $request->user();

        // 【开发/测试后门】
        if (!$user) {
            $user = User::find(1);
        }

        if (!$user) {
            return response()->json(['code' => 401, 'message' => '请先登录或创建测试账号(ID=1)'], 401);
        }

        // 4. 开启事务处理
        try {
            return DB::transaction(function () use ($user, $inputBets, $totalBetAmount) {

                // --- A. 悲观锁 (防止并发刷分) ---
                // 锁定用户行，直到事务结束
                $user = User::where('id', $user->id)->lockForUpdate()->first();

                // 检查余额
                if ($user->balance < $totalBetAmount) {
                    return response()->json(['code' => 400, 'message' => '余额不足'], 400);
                }

                // --- B. 扣款 & 记录流水 ---
                $beforeBalance = $user->balance;
                $user->decrement('balance', $totalBetAmount);
                $afterBalance = $user->balance;

                // 记录下注流水
                Transaction::create([
                    'user_id'        => $user->id,
                    'type'           => 3, // 3=下注
                    'amount'         => -$totalBetAmount,
                    'before_balance' => $beforeBalance,
                    'after_balance'  => $afterBalance,
                    'remark'         => '下注: ' . json_encode($inputBets)
                ]);

                // --- C. 核心算法 (决定停在哪里) ---
                // 这是一个 0 - 23 的索引
                // TODO: 如果需要控制杀率(RTP)，请在这里替换为权重随机算法
                $stopIndex = rand(0, count($this->boardLayout) - 1);

                // 根据停止位置，获取对应的水果ID
                $resultFruitId = $this->boardLayout[$stopIndex];

                // --- D. 结算输赢 ---
                // 检查用户在这个水果上压了多少钱
                $betOnThis = $inputBets[$resultFruitId] ?? 0;
                $winAmount = 0;

                if ($betOnThis > 0) {
                    // 查赔率
                    $multiplier = $this->odds[$resultFruitId] ?? 0;
                    $winAmount = $betOnThis * $multiplier;
                }

                // --- E. 派彩 ---
                if ($winAmount > 0) {
                    $beforeWin = $user->balance;
                    $user->increment('balance', $winAmount);
                    $afterWin = $user->balance;

                    // 记录中奖流水
                    Transaction::create([
                        'user_id'        => $user->id,
                        'type'           => 4, // 4=派彩
                        'amount'         => $winAmount,
                        'before_balance' => $beforeWin,
                        'after_balance'  => $afterWin,
                        'reference_id'   => $stopIndex,
                        'remark'         => "中奖: 水果ID {$resultFruitId} (赔率 {$this->odds[$resultFruitId]})"
                    ]);
                }

                // --- F. 记录游戏总日志 (后台查询用) ---
                $log = GameLog::create([
                    'user_id'       => $user->id,
                    'game_id'       => 'fruit_machine_01',
                    'bet_amount'    => $totalBetAmount,
                    'win_amount'    => $winAmount,
                    'profit'        => $winAmount - $totalBetAmount, // 玩家盈亏
                    'result_matrix' => json_encode([
                        'stop_index' => $stopIndex,
                        'fruit_id'   => $resultFruitId,
                        'bets'       => $inputBets
                    ]),
                ]);

                // --- G. 触发代理返佣 ---
                // 如果你之前写了 UserBetEvent 和 Listener，请取消下面这行的注释
                // event(new \App\Events\UserBetEvent($user, $totalBetAmount));

                // --- H. 返回结果给前端 ---
                return response()->json([
                    'code' => 200,
                    'data' => [
                        'balance'    => $user->balance,    // 最新余额
                        'stop_index' => $stopIndex,        // 停止位置 (0-23)，前端动画用
                        'fruit_id'   => $resultFruitId,    // 中了什么水果
                        'win_amount' => $winAmount,        // 赢了多少
                        'log_id'     => $log->id
                    ]
                ]);
            });

        } catch (\Exception $e) {
            // 记录错误日志到 storage/logs/laravel.log 方便排查
            \Illuminate\Support\Facades\Log::error("Spin Error: " . $e->getMessage());
            return response()->json(['code' => 500, 'message' => '系统错误: ' . $e->getMessage()], 500);
        }
    }
}
