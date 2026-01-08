<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function telegramLogin(Request $request)
    {
        $initData = $request->input('init_data');

        // 1. ★★★ 核心安全验证 ★★★
        $webAppData = $this->validateTelegramData($initData);

        if (!$webAppData) {
            return response()->json(['message' => '身份验证失败 (Invalid Signature)'], 401);
        }

        // 2. 解析用户信息
        // 验证通过后，$webAppData['user'] 里就是真实的 TG 用户数据
        $tgUser = json_decode($webAppData['user'], true);

        // 3. 自动注册/登录 (Find or Create)
        $user = User::updateOrCreate(
            ['telegram_id' => $tgUser['id']], // 查找条件：TG ID
            [
                'name' => $tgUser['first_name'], // 更新名字
                'username' => $tgUser['username'] ?? null,
                // 给一个随机密码，因为根本用不到密码登录
                'password' => bcrypt(Str::random(32)),
            ]
        );

        // 4. 颁发 Sanctum Token
        // $user->tokens()->delete(); // 可选：由你决定是否允许单点登录
        $token = $user->createToken('webapp')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * 验证 Telegram 发来的数据签名 (HMAC-SHA256)
     * 代码来自 Telegram 官方文档
     */
    private function validateTelegramData($initData)
    {
        if (!$initData) return false;

        // A. 解析字符串为数组
        parse_str($initData, $data);
        $checkHash = $data['hash'] ?? '';
        unset($data['hash']); // 移除 hash，剩下的部分参与计算

        // B. 排序
        ksort($data);

        // C. 拼接
        $dataCheckString = '';
        foreach ($data as $key => $value) {
            $dataCheckString .= ($dataCheckString ? "\n" : "") . $key . '=' . $value;
        }

        // D. 计算密钥
        // 这里的 Bot Token 必须和你 .env 里的一致
        $botToken = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);

        // E. 计算 Hash
        $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        // F. 对比
        if (strcmp($hash, $checkHash) === 0) {
            return $data; // 验证通过
        }

        return false;
    }
}
