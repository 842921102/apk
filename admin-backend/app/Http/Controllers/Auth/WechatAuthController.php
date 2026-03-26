<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WechatAuthController
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:10'],
        ]);

        $appid = env('WECHAT_APP_ID');
        $secret = env('WECHAT_APP_SECRET');
        if (! is_string($appid) || $appid === '' || ! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages([
                'code' => ['未配置微信登录：请在 .env 设置 WECHAT_APP_ID / WECHAT_APP_SECRET。'],
            ]);
        }

        $resp = Http::timeout(10)->get('https://api.weixin.qq.com/sns/jscode2session', [
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $data['code'],
            'grant_type' => 'authorization_code',
        ]);

        $body = $resp->json();

        if (! is_array($body)) {
            return response()->json([
                'error' => ['message' => 'wechat_jscode2session_invalid_response'],
            ], 502);
        }

        if (isset($body['errcode']) && $body['errcode']) {
            return response()->json([
                'error' => ['message' => 'wechat_login_failed', 'detail' => $body],
            ], 400);
        }

        $openid = isset($body['openid']) ? (string) $body['openid'] : '';
        $unionid = isset($body['unionid']) ? (string) $body['unionid'] : '';
        if ($openid === '') {
            return response()->json([
                'error' => ['message' => 'wechat_openid_missing', 'detail' => $body],
            ], 400);
        }

        $uniqueKey = $unionid !== '' ? $unionid : $openid;
        $email = $uniqueKey.'@wechat.local';
        $nickname = '微信用户';

        $user = User::query()->firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->password = Hash::make(Str::random(32));
        }
        $user->name = $nickname;
        $user->role = 'user';
        $user->wechat_openid = $openid;
        $user->wechat_unionid = $unionid !== '' ? $unionid : null;
        $user->email_verified_at = now();
        $user->save();

        $accessToken = $this->makeAccessToken((string) $user->id);

        return response()->json([
            'access_token' => $accessToken,
            'openid' => $openid,
            'unionid' => $unionid !== '' ? $unionid : null,
            'user' => [
                'id' => (string) $user->id,
                'nickname' => $user->name,
            ],
        ]);
    }

    private function makeAccessToken(string $sub): string
    {
        $iat = time();
        $payload = [
            'sub' => $sub,
            'iat' => $iat,
            'iss' => 'admin-backend',
        ];

        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $secret = (string) config('app.key');
        $sig = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $sigEncoded = $this->base64UrlEncode($sig);

        return 'laravel_access_'.$payloadEncoded.'.'.$sigEncoded;
    }

    private function base64UrlEncode(string $raw): string
    {
        $b64 = base64_encode($raw);

        return str_replace(['+', '/', '='], ['-', '_', ''], $b64);
    }
}
