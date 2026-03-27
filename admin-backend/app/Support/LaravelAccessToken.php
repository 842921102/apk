<?php

namespace App\Support;

use App\Models\User;

/**
 * 与 {@see \App\Http\Controllers\Auth\WechatAuthController} 签发格式一致的小程序 access_token。
 */
final class LaravelAccessToken
{
    public const PREFIX = 'laravel_access_';

    public static function mintForUserId(string $userId): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'iss' => 'admin-backend',
        ];

        $payloadEncoded = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $secret = (string) config('app.key');
        $sig = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $sigEncoded = self::base64UrlEncode($sig);

        return self::PREFIX.$payloadEncoded.'.'.$sigEncoded;
    }

    public static function verifyAndResolveUser(?string $rawToken): ?User
    {
        if ($rawToken === null || $rawToken === '') {
            return null;
        }

        if (! str_starts_with($rawToken, self::PREFIX)) {
            return null;
        }

        $without = substr($rawToken, strlen(self::PREFIX));
        $parts = explode('.', $without, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadEncoded, $sigEncoded] = $parts;
        $secret = (string) config('app.key');
        $expectedSig = hash_hmac('sha256', $payloadEncoded, $secret, true);
        $sig = self::base64UrlDecode($sigEncoded);
        if ($sig === '' || ! hash_equals($expectedSig, $sig)) {
            return null;
        }

        $json = self::base64UrlDecode($payloadEncoded);
        if ($json === '') {
            return null;
        }

        $payload = json_decode($json, true);
        if (! is_array($payload) || ! isset($payload['sub'])) {
            return null;
        }

        $sub = (string) $payload['sub'];
        $user = User::query()->find($sub);
        if ($user === null || ! $user->is_active) {
            return null;
        }

        return $user;
    }

    private static function base64UrlEncode(string $raw): string
    {
        $b64 = base64_encode($raw);

        return str_replace(['+', '/', '='], ['-', '_', ''], $b64);
    }

    private static function base64UrlDecode(string $b64url): string
    {
        $b64 = str_replace(['-', '_'], ['+', '/'], $b64url);
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $raw = base64_decode($b64, true);

        return $raw === false ? '' : $raw;
    }
}
