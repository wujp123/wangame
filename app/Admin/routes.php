<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->resource('users', UserController::class);
    $router->resource('game-logs', GameLogController::class);
    $router->resource('jackpots', JackpotController::class);
    $router->resource('fruit-configs', FruitConfigController::class);
    $router->resource('deposits', DepositController::class);
    $router->resource('withdrawals', WithdrawalController::class);
    $router->resource('transactions', TransactionController::class);
    $router->resource('fund-flows', FundFlowController::class);
    $router->resource('payment-channels', PaymentChannelController::class);

});
