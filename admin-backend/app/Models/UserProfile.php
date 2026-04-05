<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'birthday',
    'gender',
    'height_cm',
    'weight_kg',
    'target_weight_kg',
    'flavor_preferences',
    'taboo_ingredients',
    'diet_preferences',
    'diet_goal',
    'cuisine_preferences',
    'dislike_ingredients',
    'allergy_ingredients',
    'cooking_frequency',
    'meal_pattern',
    'family_size',
    'lifestyle_tags',
    'religious_restrictions',
    'period_tracking',
    'health_goal',
    'recommendation_style',
    'destiny_mode_enabled',
    'period_feature_enabled',
    'accepts_product_recommendation',
    'onboarding_completed_at',
    'onboarding_version',
])]
class UserProfile extends Model
{
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'flavor_preferences' => 'array',
            'taboo_ingredients' => 'array',
            'diet_preferences' => 'array',
            'diet_goal' => 'array',
            'cuisine_preferences' => 'array',
            'dislike_ingredients' => 'array',
            'allergy_ingredients' => 'array',
            'lifestyle_tags' => 'array',
            'religious_restrictions' => 'array',
            'period_tracking' => 'array',
            'height_cm' => 'integer',
            'weight_kg' => 'float',
            'target_weight_kg' => 'float',
            'destiny_mode_enabled' => 'boolean',
            'period_feature_enabled' => 'boolean',
            'accepts_product_recommendation' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'onboarding_version' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
