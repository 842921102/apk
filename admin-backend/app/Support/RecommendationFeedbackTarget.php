<?php

namespace App\Support;

enum RecommendationFeedbackTarget: string
{
    case Result = 'result';
    case ReasonText = 'reason_text';
    case DestinyText = 'destiny_text';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
