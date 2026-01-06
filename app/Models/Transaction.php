<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'type', // 1:充值, 2:提现, 3:下注, 4:派彩, 5:佣金
        'amount',
        'before_balance',
        'after_balance',
        'reference_id', // 关联ID (比如 GameLog ID)
        'remark'
    ];
}
