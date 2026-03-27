<?php

namespace App\Support;

/**
 * 小程序收藏来源，与前端 source_type 字符串一致。
 */
enum FavoriteSourceType: string
{
    case TodayEat = 'today_eat';
    case TableDesign = 'table_design';
    case FortuneCooking = 'fortune_cooking';
    case SauceDesign = 'sauce_design';
    case Gallery = 'gallery';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
