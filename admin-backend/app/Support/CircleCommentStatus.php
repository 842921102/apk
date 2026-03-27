<?php

namespace App\Support;

final class CircleCommentStatus
{
    public const Normal = 'normal';

    public const Deleted = 'deleted';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Normal => '正常',
            self::Deleted => '已删除',
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::Normal, self::Deleted];
    }
}
