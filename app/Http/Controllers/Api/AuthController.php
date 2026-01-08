<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function telegramLogin(Request $request)
    {
        $initData = $request->input('init_data');

        // 0. 基本判空
        if (!$initData) {
            return response()->json(['message' => '未提供 init_data'], 400);
        }

        // 1. ★★★ 核心安全验证 ★★★
        $webAppData = $this->validateTelegramData($initData);

        if (!$webAppData) {
            Log::warning('Telegram 登录签名验证失败', ['data' => $initData]);
            return response()->json(['message' => '身份验证失败 (Invalid Signature)'], 401);
        }

        // 2. 解析用户信息
        // 注意：$webAppData['user'] 是一个 JSON 字符串，必须解码
        $tgUser = json_decode($webAppData['user'] ?? '{}', true);

        if (!isset($tgUser['id'])) {
            Log::error('Telegram 数据解析缺少 ID', ['user_data' => $webAppData]);
            return response()->json(['message' => '无效的用户数据'], 400);
        }

        // 记录一下日志，方便调试
        Log::info('Telegram 用户登录:', ['id' => $tgUser['id'], 'name' => $tgUser['first_name']]);

        // 3. 自动注册/登录 (Find or Create)
        // ⚠️ 请确保数据库字段是 'telegram_id'，如果是 'tg_id' 请自行修改下方代码
        $user = User::updateOrCreate(
            ['telegram_id' => $tgUser['id']], // 查找条件
            [
                'name'     => $tgUser['first_name'] . (isset($tgUser['last_name']) ? ' ' . $tgUser['last_name'] : ''),
                'username' => $tgUser['username'] ?? null,
                // 如果 TG 传了头像，顺便存一下 (数据库需有 avatar 字段，没有就删掉这行)
                // 'avatar'   => $tgUser['photo_url'] ?? null,
                'password' => bcrypt(Str::random(32)), // 随机密码
            ]
        );

        // 4. 颁发 Sanctum Token
        // $user->tokens()->delete(); // 可选：是否踢掉其他设备的登录
        $token = $user->createToken('webapp')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user
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
