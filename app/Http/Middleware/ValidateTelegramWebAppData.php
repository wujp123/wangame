<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ValidateTelegramWebAppData
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. 获取前端传来的 initData 字符串
        // 前端通常放在 Header: 'X-Telegram-Data' 或 Authorization 中
        $initData = $request->header('X-Telegram-Data');

        if (!$initData) {
            return response()->json(['message' => 'Missing authentication data'], 401);
        }

        // 2. 验证 Hash 是否合法
        if (!$this->checkHash($initData)) {
            return response()->json(['message' => 'Invalid authentication data'], 403);
        }

        // 3. 解析用户数据
        $tgUser = $this->parseUserFromInitData($initData);

        if (!$tgUser) {
            return response()->json(['message' => 'User data not found'], 400);
        }

        // 4. 自动注册或登录逻辑
        // 查找是否已有用户，没有则创建
        $user = User::firstOrCreate(
            ['tg_id' => $tgUser['id']],
            [
                'username' => $tgUser['username'] ?? 'User_' . $tgUser['id'],
                // 如果是新用户，这里可以处理邀请码逻辑(后面再细化)
                // 'name' => $tgUser['first_name'] . ' ' . ($tgUser['last_name'] ?? ''),
                'avatar' => $tgUser['photo_url'] ?? null,
            ]
        );

        // 登录该用户，以便后续 Controller 可以使用 Auth::user()
        Auth::login($user);

        return $next($request);
    }

    /**
     * 核心验证算法：校验 Telegram 签名
     * 参考官方文档: https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
     */
    private function checkHash(string $initData): bool
    {
        // 1. 解析查询字符串
        parse_str($initData, $data);

        // 必须包含 hash 字段
        if (!isset($data['hash'])) {
            return false;
        }

        $receivedHash = $data['hash'];
        unset($data['hash']); // 验证时需要移除 hash 字段本身

        // 2. 必须按键名(key)字母顺序排序
        ksort($data);

        // 3. 拼接数据字符串 key=value\n
        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        // 4. 计算密钥
        // 注意：WebAppData 是固定的常量字符串
        $botToken = env('TELEGRAM_BOT_TOKEN');
        if (!$botToken) {
            Log::error('Telegram Bot Token is missing in .env');
            return false;
        }

        // 第一步 HMAC：用 WebAppData 作为 key，BotToken 作为 data，生成二进制密钥
        $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);

        // 第二步 HMAC：用生成的密钥加密 dataCheckString
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        // 5. 对比 Hash (使用 hash_equals 防止时序攻击)
        if (!hash_equals($calculatedHash, $receivedHash)) {
            Log::warning('Telegram Hash Mismatch', ['calculated' => $calculatedHash, 'received' => $receivedHash]);
            return false;
        }

        // 6. 验证时间戳 (防止重放攻击)
        // auth_date 是 Unix 时间戳，建议限制在 24 小时内
        if (isset($data['auth_date'])) {
            if (time() - $data['auth_date'] > 86400) {
                Log::warning('Telegram Auth Date Expired');
                return false;
            }
        }

        return true;
    }

    /**
     * 解析 initData 中的 user JSON 字符串
     */
    private function parseUserFromInitData(string $initData): ?array
    {
        parse_str($initData, $data);

        if (!isset($data['user'])) {
            return null;
        }

        // user 字段是一个 JSON 字符串
        return json_decode($data['user'], true);
    }
}
