<?php

namespace App\Support;

enum RecommendationEventType: string
{
    case RecommendationView = 'recommendation_view';
    case RecommendationReroll = 'recommendation_reroll';
    case RecommendationSelectAlternative = 'recommendation_select_alternative';
    case RecipeView = 'recipe_view';
    case RecipeFavorite = 'recipe_favorite';
    case RecommendationFeedback = 'recommendation_feedback';
    case RecommendationAccept = 'recommendation_accept';
    case RecommendationReject = 'recommendation_reject';
    case OnboardingCompleted = 'onboarding_completed';
    case ProfilePreferenceUpdated = 'profile_preference_updated';
    /** 推荐链路异常或失败（若业务侧有写入） */
    case Error = 'error';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
