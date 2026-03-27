<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Support\LaravelAccessToken;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WechatAuthController
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:10'],
        ]);

        // 开发兜底：当开发环境无法访问微信 jscode2session（网络/代理/白名单等）
        // 允许通过环境变量直接绑定到已有后台用户，继续联调收藏等链路。
        //
        // 开启方式（仅开发）：
        // WECHAT_DEV_BYPASS=true
        // WECHAT_DEV_FORCE_USER_ID=2
        if (filter_var(env('WECHAT_DEV_BYPASS', false), FILTER_VALIDATE_BOOLEAN)) {
            $forceUserIdRaw = env('WECHAT_DEV_FORCE_USER_ID');
            $forceUserId = is_numeric($forceUserIdRaw) ? (int) $forceUserIdRaw : null;

            if ($forceUserId !== null) {
                $user = User::query()->find($forceUserId);

                if (! $user) {
                    return response()->json([
                        'error' => [
                            'message' => 'wechat_dev_user_not_found',
                            'detail' => "WECHAT_DEV_FORCE_USER_ID={$forceUserId}",
                        ],
                    ], 500);
                }

                $user->last_login_at = now();
                $user->save();

                if (! $user->is_active) {
                    return response()->json([
                        'error' => [
                            'message' => 'account_disabled',
                            'detail' => '该账号已被管理员禁用',
                        ],
                    ], 403);
                }

                $accessToken = LaravelAccessToken::mintForUserId((string) $user->id);

                return response()->json([
                    'access_token' => $accessToken,
                    'openid' => (string) ($user->wechat_openid ?? ''),
                    'unionid' => $user->wechat_unionid !== null ? (string) $user->wechat_unionid : null,
                    'user' => [
                        'id' => (string) $user->id,
                        'nickname' => $user->name,
                    ],
                ]);
            }

            // 若未强制绑定用户，则按 code 创建一个“开发账号”
            // 注意：openid/email 仅用于本地联调，不用于上线。
            $uniqueKey = 'dev_'.hash('sha256', (string) $data['code']);
            $email = $uniqueKey.'@wechat.local';
            $openid = $uniqueKey;

            $user = User::query()->firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->password = Hash::make(Str::random(32));
            }

            $user->name = '微信用户（开发兜底）';
            $user->role = 'user';
            $user->wechat_openid = $openid;
            $user->wechat_unionid = null;
            $user->email_verified_at = now();
            $user->last_login_at = now();
            $user->is_active = $user->is_active ?? true;
            $user->save();

            $accessToken = LaravelAccessToken::mintForUserId((string) $user->id);

            return response()->json([
                'access_token' => $accessToken,
                'openid' => $openid,
                'unionid' => null,
                'user' => [
                    'id' => (string) $user->id,
                    'nickname' => $user->name,
                ],
            ]);
        }

        $appid = env('WECHAT_APP_ID');
        $secret = env('WECHAT_APP_SECRET');
        if (! is_string($appid) || $appid === '' || ! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages([
                'code' => ['未配置微信登录：请在 .env 设置 WECHAT_APP_ID / WECHAT_APP_SECRET。'],
            ]);
        }

        try {
            // 关键：禁用当前环境的 http(s) 代理，避免代理对微信域名的 CONNECT 拦截（403）。
            // 仅影响这一条 wechat 登录上游请求。
            $resp = Http::timeout(10)
                ->withOptions([
                    'proxy' => null,
                ])
                ->get('https://api.weixin.qq.com/sns/jscode2session', [
                    'appid' => $appid,
                    'secret' => $secret,
                    'js_code' => $data['code'],
                    'grant_type' => 'authorization_code',
                ]);

            if (! $resp->successful()) {
                $raw = (string) $resp->body();
                $snippet = mb_substr($raw, 0, 300);

                return response()->json([
                    'error' => [
                        'message' => 'wechat_login_failed',
                        'detail' => "wechat_http_status={$resp->status()} body_snippet={$snippet}",
                    ],
                ], 502);
            }

            $body = $resp->json();
        } catch (RequestException $e) {
            $status = $e->response?->status();
            $raw = (string) ($e->response?->body() ?? '');
            $snippet = mb_substr($raw, 0, 300);

            return response()->json([
                'error' => [
                    'message' => 'wechat_login_failed',
                    'detail' => "request_exception status={$status} body_snippet={$snippet}",
                ],
            ], 502);
        } catch (Throwable $e) {
            return response()->json([
                'error' => [
                    'message' => 'wechat_login_failed',
                    'detail' => $e->getMessage(),
                ],
            ], 502);
        }

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
        // 兼容：扩展迁移后老数据/新数据中 `is_active` 可能为 null，
        // PHP `if (! $user->is_active)` 会把 null 当作禁用，导致登录 403。
        $user->is_active = $user->is_active ?? true;
        $user->email_verified_at = now();
        $user->last_login_at = now();
        $user->save();

        if (! $user->is_active) {
            return response()->json([
                'error' => [
                    'message' => 'account_disabled',
                    'detail' => '该账号已被管理员禁用',
                ],
            ], 403);
        }

        $accessToken = LaravelAccessToken::mintForUserId((string) $user->id);

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
}
