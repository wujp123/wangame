<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\WebhookController;

// Telegram 登录
Route::post('/auth/telegram', [AuthController::class, 'telegramLogin']);

Route::any('notify', [PaymentController::class, 'notify']);//回调

Route::post('/telegram/webhook', [BotController::class, 'webhook']);// TG 机器人消息回调

Route::post('/webhook/nowpayments', [WebhookController::class, 'handleNowPayments'])->name('webhook.nowpayments');
// 应用中间件 tg.auth
Route::middleware(['tg.auth'])->group(function () {

});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/game/spin', [GameController::class, 'spin']);
    // 1. 游戏核心逻辑 (必须知道扣谁的钱)
    Route::get('/game/balance', [GameController::class, 'balance']);
    Route::get('game/config', [GameController::class, 'config']);

    // ★★★ 2. 支付相关 (新增这几行) ★★★
    Route::post('deposit', [PaymentController::class, 'deposit']);   // 充值
    Route::post('withdraw', [PaymentController::class, 'withdraw']); // 提现

    // 3. 辅助接口
    // 支付通道虽然看似通用，但通常建议登录后才展示（可能根据 VIP 等级显示不同通道）
    Route::get('deposit/channels', [PaymentController::class, 'channels']);

    // 4. 用户信息
    Route::get('/user/info', function (Request $request) {
        return $request->user();
    });
});
