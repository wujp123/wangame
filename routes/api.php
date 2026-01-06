<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GameController;


//Route::post('/game/spin', [GameController::class, 'spin']);
//// 旋转老虎机
//Route::get('/user/balance', [GameController::class, 'balance']);
// 应用中间件 tg.auth
Route::middleware(['tg.auth'])->group(function () {

    // 获取用户信息
//    Route::get('/user/info', function (Request $request) {
//        return $request->user();
//    });
    // 暂时放出来方便测试，正式上线请放回 middleware 里面
    Route::post('/game/spin', [GameController::class, 'spin']);
    // 旋转老虎机
    Route::get('/user/balance', [GameController::class, 'balance']);

});
