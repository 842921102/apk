<?php

namespace App\Support;

enum RecommendationFeedbackReason: string
{
    case TooGreasy = 'too_greasy';
    case TooComplex = 'too_complex';
    case WrongFlavor = 'wrong_flavor';
    case AlreadyAteRecently = 'already_ate_recently';
    case NotFitGoal = 'not_fit_goal';
    case NotFitTodayStatus = 'not_fit_today_status';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
