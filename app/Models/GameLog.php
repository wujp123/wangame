<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// 记得引入 User 模型
use App\Models\User;

class GameLog extends Model
{
    // 允许批量赋值的字段
    protected $fillable = [
        'user_id',
        'game_id',
        'bet_amount',
        'win_amount',
        'profit',
        'result_matrix'
    ];

    // --- 请添加下面这个方法 ---
    public function user()
    {
        // 这里的 'user_id' 是 game_logs 表里的外键字段
        return $this->belongsTo(User::class, 'user_id');
    }
}
