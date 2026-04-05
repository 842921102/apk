<?php

namespace App\Support;

enum RecommendationFeedbackType: string
{
    case WantToEat = 'want_to_eat';
    case NotToday = 'not_today';
    case ChangeDirection = 'change_direction';
    case ReasonAccurate = 'reason_accurate';
    case NotSuitable = 'not_suitable';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function requiresSecondaryReason(): bool
    {
        return $this === self::NotToday || $this === self::NotSuitable;
    }
}
