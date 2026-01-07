<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $guarded = []; // 允许所有字段批量赋值

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
