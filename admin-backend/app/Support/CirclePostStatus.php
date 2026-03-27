<?php

namespace App\Support;

/** 圈子帖子运营状态（不含软删除：删除走 deleted_at） */
final class CirclePostStatus
{
    public const Normal = 'normal';

    public const Offline = 'offline';

    public const Deleted = 'deleted';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Normal => '正常',
            self::Offline => '已下架',
            self::Deleted => '已删除',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::Normal, self::Offline, self::Deleted];
    }
}
