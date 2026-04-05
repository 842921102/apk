<?php

namespace App\Support;

/**
 * 用户动态偏好信号维度（与推荐菜品结构化字段对齐）。
 */
final class PreferenceSignalType
{
    public const Flavor = 'flavor';

    public const Cuisine = 'cuisine';

    public const HealthTag = 'health_tag';

    public const MoodTag = 'mood_tag';

    public const Scene = 'scene';

    public const CookingComplexity = 'cooking_complexity';

    public const Dish = 'dish';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::Flavor,
            self::Cuisine,
            self::HealthTag,
            self::MoodTag,
            self::Scene,
            self::CookingComplexity,
            self::Dish,
        ];
    }
}
