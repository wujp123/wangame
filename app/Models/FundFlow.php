<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundFlow extends Model
{
    protected $guarded = [];

    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
