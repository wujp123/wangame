<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BotController;

// Telegram 登录
Route::post('/auth/telegram', [AuthController::class, 'telegramLogin']);

Route::post('/game/spin', [GameController::class, 'spin']);
//// 旋转老虎机
Route::get('/game/balance', [GameController::class, 'balance']);

Route::get('game/config', [GameController::class, 'config']);

// ★★★ 2. 支付相关 (新增这几行) ★★★
Route::post('deposit', [PaymentController::class, 'deposit']);   // 充值
Route::post('withdraw', [PaymentController::class, 'withdraw']); // 提现
Route::any('notify', [PaymentController::class, 'notify']);//回调
Route::get('deposit/channels', [PaymentController::class, 'channels']);//通道
Route::post('/telegram/webhook', [BotController::class, 'webhook']);
// 应用中间件 tg.auth
Route::middleware(['tg.auth'])->group(function () {

    // 获取用户信息
//    Route::get('/user/info', function (Request $request) {
//        return $request->user();
//    });
    // 暂时放出来方便测试，正式上线请放回 middleware 里面
//    Route::post('/game/spin', [GameController::class, 'spin']);
    // 旋转老虎机
//    Route::get('/user/balance', [GameController::class, 'balance']);

//    Route::get('config', [GameController::class, 'config']);

});

Route::middleware('auth:sanctum')->group(function () {
    // ...
});
