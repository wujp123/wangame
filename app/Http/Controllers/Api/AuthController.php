<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function telegramLogin(Request $request)
    {
        // 1. 获取前端传来的 initData 字符串
        $initData = $request->input('initData');

        if (!$initData) {
            return response()->json(['message' => 'No data provided'], 400);
        }

        // 2. 解析 initData
        parse_str($initData, $data);

        // 3. 验证签名 (这是 Telegram 官方要求的安全验证流程)
        $botToken = env('TELEGRAM_BOT_TOKEN'); // 请确保 .env 里配置了 Token
        if (!$botToken) {
            return response()->json(['message' => 'Bot token not config'], 500);
        }

        $checkHash = $data['hash'] ?? '';
        unset($data['hash']);

        // 按键名排序
        ksort($data);

        // 拼接数据字符串
        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        // 计算 HMAC
        $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);
        $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        // 验证失败
        if (strcmp($hash, $checkHash) !== 0) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // 4. 验证通过，获取用户信息
        // Telegram 传回的 user 字段是一个 JSON 字符串
        $tgUser = json_decode($data['user'], true);
        $tgId = $tgUser['id'];
        $username = $tgUser['username'] ?? 'Guest';
        $firstName = $tgUser['first_name'] ?? 'Guest';

        // 5. 查找或创建用户 (Auto Register)
        // 我们假设 email 字段存 tg_id (或者你在 users 表加个 telegram_id 字段)
        // 这里为了简单，直接用 email 存 "tg_ID@telegram"
        $fakeEmail = "tg_{$tgId}@telegram.com";

        $user = User::firstOrCreate(
            ['email' => $fakeEmail],
            [
                'name' => $firstName,
                'password' => bcrypt(Str::random(16)), // 随机密码
                'balance' => 0 // 新用户余额为 0 (或者送 100 体验金)
            ]
        );

        // 6. 发放 Sanctum Token
        // 先删除旧 Token (可选，防止单点登录)
        $user->tokens()->delete();
        $token = $user->createToken('tg-login')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }
}
