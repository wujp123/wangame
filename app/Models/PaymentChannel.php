<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentChannel extends Model
{
    protected $guarded = [];
    protected $casts = [
        'config' => 'json', // 自动转换 JSON
        'is_active' => 'boolean'
    ];
}
