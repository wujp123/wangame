<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
        'tg_id',  // TG 的唯一数字 ID (注意：如果你数据库字段叫 tg_id，这里要改成 'tg_id')
        'username',     // TG 的用户名 (@xxx)
        'parent_id',    // 上级 ID (用于绑定邀请关系)
        'avatar',       // 头像 (如果有)
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 关联：我的上级
    public function parent()
    {
        // 这里的 parent_id 是 users 表里的字段，指向 id
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    // 关联：我的下级 (我邀请了谁) - 以后做推广系统会用到
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id', 'id');
    }
}
