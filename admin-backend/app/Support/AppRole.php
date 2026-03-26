<?php

namespace App\Support;

/**
 * 与前端 src/lib/appRoles.ts 保持一致：user / viewer / operator / super_admin（旧 admin 视作 super_admin）。
 */
final class AppRole
{
    public const VALUES = ['user', 'viewer', 'operator', 'super_admin'];

    public static function normalize(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return 'user';
        }

        $normalized = strtolower(preg_replace('/[\s\-]+/', '_', trim($raw)));

        if (in_array($normalized, ['admin', 'superadmin', 'super_admin'], true)) {
            return 'super_admin';
        }

        if (in_array($normalized, ['viewer', 'operator', 'user'], true)) {
            return $normalized;
        }

        return 'user';
    }

    public static function canAccessAdmin(?string $raw): bool
    {
        $r = self::normalize($raw);

        return in_array($r, ['viewer', 'operator', 'super_admin'], true);
    }

    public static function labelCn(string $role): string
    {
        return match (self::normalize($role)) {
            'user' => '普通用户',
            'viewer' => '只读审核员',
            'operator' => '运营人员',
            'super_admin' => '超级管理员',
            default => '普通用户',
        };
    }
}
