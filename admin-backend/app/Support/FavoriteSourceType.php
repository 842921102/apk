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
    /** 今日推荐历史单条（关联 recommendation_records.id） */
    case RecommendationRecord = 'recommendation_record';

    /** 后台标准菜谱（关联 dish_recipes.id） */
    case Recipe = 'recipe';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
