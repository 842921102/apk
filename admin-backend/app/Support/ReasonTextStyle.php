<?php

namespace App\Support;

/**
 * 推荐理由（reason_text）产品线解释风格。
 */
enum ReasonTextStyle: string
{
    case Practical = 'practical';
    case Caring = 'caring';
    case GoalOriented = 'goal_oriented';
    case SceneBased = 'scene_based';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
