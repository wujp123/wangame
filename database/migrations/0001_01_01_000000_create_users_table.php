<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // --- 基础字段调整 ---
            $table->string('name')->nullable(); // Telegram 的名字可能为空，或者你存 first_name
            $table->string('email')->unique()->nullable(); // TG 用户可能没邮箱，必须 nullable
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // TG 登录不需要密码，必须 nullable
            $table->rememberToken();
            $table->timestamps();

            // --- 老虎机核心字段 ---
            $table->string('tg_id')->unique()->nullable()->comment('Telegram ID'); // 加上 index 方便快速查找
            $table->string('username')->nullable()->comment('TG 用户名');
            $table->string('avatar')->nullable()->comment('头像URL');
            $table->decimal('balance', 20, 2)->default(0)->comment('积分余额');
            $table->decimal('frozen_balance', 20, 2)->default(0)->comment('冻结金额');
            $table->tinyInteger('status')->default(1)->comment('1:正常 0:封禁');

            // --- 代理系统字段 (你之前要求的) ---
            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('上级ID');
            $table->string('invite_code', 10)->unique()->nullable()->comment('我的邀请码');
            $table->string('parent_path')->nullable()->comment('关系路径，如: 0-1-5');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
