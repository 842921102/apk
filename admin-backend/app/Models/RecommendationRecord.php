<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property string $recommendation_source
 * @property Carbon $recommendation_date
 * @property string $meal_type
 * @property string $recommended_dish
 * @property list<string>|null $tags
 * @property string $reason_text
 * @property string|null $destiny_text
 * @property string|null $destiny_style
 * @property string|null $reason_style
 * @property list<string>|null $alternatives
 * @property string|null $cuisine
 * @property list<string>|null $ingredients
 * @property string $recipe_content
 * @property array<string, mixed>|null $weather_snapshot
 * @property array<string, mixed>|null $festival_snapshot
 * @property array<string, mixed>|null $user_profile_snapshot
 * @property array<string, mixed>|null $daily_status_snapshot
 * @property bool $is_favorited
 * @property bool $recommendation_fallback
 * @property bool $used_default_profile
 */
#[Fillable([
    'user_id',
    'session_id',
    'recommendation_source',
    'recommendation_date',
    'meal_type',
    'recommended_dish',
    'tags',
    'reason_text',
    'destiny_text',
    'destiny_style',
    'reason_style',
    'alternatives',
    'cuisine',
    'ingredients',
    'recipe_content',
    'weather_snapshot',
    'festival_snapshot',
    'user_profile_snapshot',
    'daily_status_snapshot',
    'is_favorited',
    'recommendation_fallback',
    'used_default_profile',
])]
class RecommendationRecord extends Model
{
    protected function casts(): array
    {
        return [
            'recommendation_date' => 'date',
            'tags' => 'array',
            'alternatives' => 'array',
            'ingredients' => 'array',
            'weather_snapshot' => 'array',
            'festival_snapshot' => 'array',
            'user_profile_snapshot' => 'array',
            'daily_status_snapshot' => 'array',
            'is_favorited' => 'boolean',
            'recommendation_fallback' => 'boolean',
            'used_default_profile' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedbackRecords(): HasMany
    {
        return $this->hasMany(RecommendationFeedbackRecord::class);
    }
}
