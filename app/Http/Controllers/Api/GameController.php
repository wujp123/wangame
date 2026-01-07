<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\GameLog;
use App\Models\Transaction;
use App\Models\FruitConfig; // 确保你创建了这个 Model

class GameController extends Controller
{
    // 定义特殊 ID: Lucky
    const ID_LUCKY = 9;

    /**
     * 跑马灯物理布局 (外圈 24 个格子对应的水果 ID)
     * 顺序：从左上角开始 -> 上排(往右) -> 右排(往下) -> 下排(往左) -> 左排(往上)
     * 必须与前端 Pixi.js 的绘制顺序逻辑一致
     */
    protected $boardLayout = [
        // 上排 (0-6)
        9, 6, 5, 4, 3, 2, 1,
        // 右排 (7-12)
        1, 8, 8, 7, 6, 9,
        // 下排 (13-18)
        4, 3, 2, 8, 8, 7,
        // 左排 (19-23)
        6, 5, 8, 8, 7
    ];

    /**
     * 前端配置接口
     * 前端加载时调用此接口，获取赔率、名称、布局
     */
    public function config()
    {
        // 缓存 60秒，避免高并发频繁查库
        $configs = Cache::remember('game_fruit_configs', 60, function () {
            return FruitConfig::orderBy('id')->get(['id', 'name', 'multiplier', 'weight']);
        });

        // 颜色映射 (通常数据库不存CSS样式，这里手动映射)
        $colorMap = [
            1 => 'c-bar', 2 => 'c-77', 3 => 'c-star', 4 => 'c-water',
            5 => 'c-bell', 6 => 'c-lemon', 7 => 'c-orange', 8 => 'c-apple',
            9 => 'c-lucky'
        ];

        $fruits = $configs->map(function ($item) use ($colorMap) {
            return [
                'id'         => $item->id,
                'name'       => $item->name,
                'multiplier' => $item->multiplier,
                'color'      => $colorMap[$item->id] ?? 'c-apple',
            ];
        });

        return response()->json([
            'code' => 200,
            'data' => [
                'fruits' => $fruits,
                'layout' => $this->boardLayout
            ]
        ]);
    }

    /**
     * 获取余额接口
     */
    public function balance(Request $request)
    {
        $user = $request->user();
        if (!$user) $user = User::find(1); // 测试用

        if (!$user) return response()->json(['code' => 404, 'message' => 'User not found'], 404);

        $jackpot = \App\Models\Jackpot::find(1);
        $poolBalance = $jackpot ? $jackpot->balance : 0;

        return response()->json([
            'code' => 200,
            'data' => [
                'balance' => $user->balance,
                'jackpot' => $poolBalance
            ]
        ]);
    }

    /**
     * 核心旋转接口
     */
    public function spin(Request $request)
    {
        $request->validate([
            'bets'   => 'required|array',
            'bets.*' => 'integer|min:0',
        ]);

        $inputBets = $request->input('bets');
        unset($inputBets[self::ID_LUCKY]);

        $totalBetAmount = array_sum($inputBets);

        if ($totalBetAmount <= 0) {
            return response()->json(['code' => 400, 'message' => '请至少下注一项'], 400);
        }

        $user = $request->user();
        if (!$user) $user = User::find(1);
        if (!$user) return response()->json(['code' => 401, 'message' => 'User not found'], 401);

        try {
            return DB::transaction(function () use ($user, $inputBets, $totalBetAmount) {

                // --- A. 锁定数据 ---
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $jackpot = \App\Models\Jackpot::where('id', 1)->lockForUpdate()->first();

                if ($user->balance < $totalBetAmount) {
                    return response()->json(['code' => 400, 'message' => '余额不足'], 400);
                }

                // --- B. 扣款 & 进奖池 ---
                $taxRate = $jackpot->tax_rate ?? 5;
                $taxAmount = $totalBetAmount * ($taxRate / 100);
                $poolContribution = $totalBetAmount - $taxAmount;

                $beforeBalance = $user->balance;
                $user->decrement('balance', $totalBetAmount);
                $jackpot->increment('balance', $poolContribution);

                // ★★★ 记录游戏积分流水 (Transaction) ★★★
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 3, // 3=下注
                    'amount' => -$totalBetAmount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $user->balance,
                    'remark' => "下注: {$totalBetAmount}"
                ]);

                // --- C. 游戏算法 ---
                $stopsPath = [];
                $finalFruitId = 0;
                $isLuckyHit = false;
                $theoreticalWin = 0; // 初始化奖金

                // 1. 第一转
                // $firstFruitId = $this->getWeightedFruitId();
                $firstFruitId = 9; // 测试强制 Lucky

                $indices1 = array_keys($this->boardLayout, $firstFruitId);
                if (empty($indices1)) { $indices1 = [0]; $firstFruitId = $this->boardLayout[0]; }
                $stopsPath[] = $indices1[array_rand($indices1)];

                // 2. 核心逻辑分支
                if ($firstFruitId == self::ID_LUCKY) {
                    // === Lucky 模式 ===
                    $isLuckyHit = true;
                    $luckyCount = (rand(1, 100) <= 20) ? 2 : 1;

                    for ($k = 0; $k < $luckyCount; $k++) {
                        $nextId = $this->getWeightedFruitId([self::ID_LUCKY]);

                        $nextIndices = array_keys($this->boardLayout, $nextId);
                        $stopsPath[] = $nextIndices[array_rand($nextIndices)];

                        // ★★★ 修复点：在循环内计算累加奖金 ★★★
                        $fruitConfig = FruitConfig::find($nextId);
                        $mult = $fruitConfig ? $fruitConfig->multiplier : 0;
                        $bet = $inputBets[$nextId] ?? 0;

                        $theoreticalWin += ($bet * $mult); // 累加

                        $finalFruitId = $nextId; // 记录最后一次的水果
                    }

                } else {
                    // === 普通模式 ===
                    $finalFruitId = $firstFruitId;

                    // ★★★ 修复点：普通模式单独计算，互不干扰 ★★★
                    $fruitConfig = FruitConfig::find($finalFruitId);
                    $mult = $fruitConfig ? $fruitConfig->multiplier : 0;
                    $bet = $inputBets[$finalFruitId] ?? 0;

                    $theoreticalWin = ($bet * $mult);
                }

                // --- E. 派彩 ---
                $actualWin = 0;
                $isCapped = false;

                if ($theoreticalWin > 0) {
                    // 奖池检测
                    if ($jackpot->balance >= $theoreticalWin) {
                        $actualWin = $theoreticalWin;
                    } else {
                        $actualWin = $jackpot->balance;
                        $isCapped = true;
                    }

                    if ($actualWin > 0) {
                        $jackpot->decrement('balance', $actualWin);
                        $user->increment('balance', $actualWin);

                        // ★★★ 记录游戏积分流水 (Transaction) ★★★
                        // 注意：这里依然是 Transaction，因为这是游戏赢分，不是充值
                        Transaction::create([
                            'user_id' => $user->id,
                            'type' => 4, // 4=派彩
                            'amount' => $actualWin,
                            'before_balance' => $user->balance - $actualWin,
                            'after_balance' => $user->balance,
                            'reference_id' => end($stopsPath),
                            'remark' => $isLuckyHit ? "中奖(Lucky)" : "中奖: ID {$finalFruitId}"
                        ]);
                    }
                }

                // --- F. 记录详细游戏日志 ---
                GameLog::create([
                    'user_id' => $user->id,
                    'game_id' => 'fruit_01',
                    'bet_amount' => $totalBetAmount,
                    'win_amount' => $actualWin,
                    'profit' => $actualWin - $totalBetAmount,
                    'result_matrix' => json_encode(['stops' => $stopsPath, 'fruit_id' => $finalFruitId]),
                ]);

                return response()->json([
                    'code' => 200,
                    'data' => [
                        'balance'    => $user->balance,
                        'stops'      => $stopsPath,
                        'final_id'   => $finalFruitId,
                        'win_amount' => $actualWin,
                        'jackpot'    => $jackpot->balance
                    ]
                ]);
            });

        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => 'System Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 权重随机
     */
    private function getWeightedFruitId($excludeIds = [])
    {
        // 缓存配置
        $configs = Cache::remember('game_fruit_weights', 60, function () {
            return FruitConfig::all(['id', 'weight']);
        });

        // 排除 ID
        if (!empty($excludeIds)) {
            $configs = $configs->reject(fn($v) => in_array($v->id, $excludeIds));
        }

        $totalWeight = $configs->sum('weight');
        if ($totalWeight <= 0) return 8; // 保底苹果

        $rand = rand(1, $totalWeight);
        $current = 0;

        foreach ($configs as $fruit) {
            $current += $fruit->weight;
            if ($rand <= $current) return $fruit->id;
        }
        return 8;
    }
}
