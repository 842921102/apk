<?php

namespace App\Services;

use App\Models\RecipeHistory;
use App\Models\User;
use App\Models\UserDailyStatus;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * 聚合「今日推荐」所需结构化上下文；天气/节日/节气/生日均由系统标准化，禁止交给模型臆造。
 */
class RecommendationContextService
{
    public const PERIOD_NONE = 'none';

    public function __construct(
        private readonly RecommendationTagService $recommendationTagService,
        private readonly DateContextService $dateContextService,
        private readonly WeatherContextService $weatherContextService,
        private readonly FestivalContextService $festivalContextService,
        private readonly UserSpecialDayService $userSpecialDayService,
    ) {}

    /**
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @return array{
     *   date_context: array<string, mixed>,
     *   weather_context: array<string, mixed>,
     *   festival_context: array<string, mixed>,
     *   user_special_context: array<string, mixed>,
     *   user_profile: array<string, mixed>,
     *   history_context: array<string, mixed>,
     *   daily_status: array<string, mixed>,
     *   session_preferences: array<string, mixed>,
     *   generated_tags: list<string>
     * }
     */
    public function aggregateForUser(
        User $user,
        ?Carbon $onDate = null,
        array $sessionPreferences = [],
        bool $withWeatherAndHistory = true,
    ): array {
        $onDate ??= Carbon::today();
        $tz = config('app.timezone');
        $now = Carbon::now($tz);

        $profileModel = $user->profile;
        $profile = $profileModel instanceof UserProfile
            ? $this->serializeProfile($profileModel)
            : $this->emptyProfileSnapshot();

        $dailyModel = UserDailyStatus::query()
            ->where('user_id', $user->id)
            ->whereDate('status_date', $onDate->toDateString())
            ->first();

        $daily = $this->serializeDaily($dailyModel, $profileModel instanceof UserProfile ? $profileModel : null);

        $dateContext = $this->dateContextService->build($onDate, $now);

        $weather = $withWeatherAndHistory
            ? $this->weatherContextService->build($onDate)
            : [
                'available' => false,
                'skipped' => true,
                'note' => '轻量模式未拉取天气',
                'weather_type' => null,
                'temperature' => null,
                'feels_like' => null,
                'humidity' => null,
                'wind_level' => null,
                'weather_tags' => [],
                'weather_code' => null,
                'description' => null,
                'is_precipitation' => false,
                'temp_c' => null,
            ];

        $festival = $this->festivalContextService->build($onDate);
        $userSpecial = $this->userSpecialDayService->build(
            $onDate,
            $profileModel instanceof UserProfile ? $profileModel : null,
        );

        $history = $withWeatherAndHistory
            ? $this->buildHistoryContext((int) $user->id)
            : ['recent_titles' => [], 'recent_items' => [], 'skipped' => true];

        $sessionPreferences = $this->normalizeSessionPreferences($sessionPreferences);

        return [
            'date_context' => $dateContext,
            'weather_context' => $weather,
            'festival_context' => $festival,
            'user_special_context' => $userSpecial,
            'user_profile' => $profile,
            'history_context' => $history,
            'daily_status' => $daily,
            'session_preferences' => $sessionPreferences,
            'generated_tags' => [],
        ];
    }

    /**
     * @return list<string>
     */
    public function buildTagsForUser(User $user, ?Carbon $onDate = null): array
    {
        $onDate ??= Carbon::today();
        $ctx = $this->aggregateForUser($user, $onDate, [], false);

        return $this->recommendationTagService->buildFromContext($ctx);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyProfileSnapshot(): array
    {
        return [
            'onboarding_completed' => false,
            'flavor_preferences' => [],
            'taboo_ingredients' => [],
            'diet_preferences' => [],
            'diet_goal' => [],
            'dislike_ingredients' => [],
            'allergy_ingredients' => [],
            'cuisine_preferences' => [],
            'lifestyle_tags' => [],
            'religious_restrictions' => [],
            'period_tracking' => [],
            'cooking_frequency' => null,
            'family_size' => null,
            'destiny_mode_enabled' => false,
            'period_feature_enabled' => false,
            'recommendation_style' => null,
            'health_goal' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(UserProfile $p): array
    {
        return [
            'birthday' => $p->birthday?->format('Y-m-d'),
            'gender' => $p->gender,
            'height_cm' => $p->height_cm,
            'weight_kg' => $p->weight_kg,
            'target_weight_kg' => $p->target_weight_kg,
            'flavor_preferences' => $p->flavor_preferences ?? [],
            'taboo_ingredients' => $p->taboo_ingredients ?? [],
            'diet_preferences' => $p->diet_preferences ?? [],
            'diet_goal' => is_array($p->diet_goal) ? $p->diet_goal : [],
            'dislike_ingredients' => $p->dislike_ingredients ?? [],
            'allergy_ingredients' => $p->allergy_ingredients ?? [],
            'cuisine_preferences' => $p->cuisine_preferences ?? [],
            'lifestyle_tags' => $p->lifestyle_tags ?? [],
            'religious_restrictions' => $p->religious_restrictions ?? [],
            'period_tracking' => is_array($p->period_tracking) ? $p->period_tracking : [],
            'cooking_frequency' => $p->cooking_frequency,
            'family_size' => $p->family_size,
            'meal_pattern' => $p->meal_pattern,
            'destiny_mode_enabled' => (bool) $p->destiny_mode_enabled,
            'period_feature_enabled' => (bool) $p->period_feature_enabled,
            'accepts_product_recommendation' => (bool) $p->accepts_product_recommendation,
            'recommendation_style' => $p->recommendation_style,
            'health_goal' => $p->health_goal,
            'onboarding_completed_at' => $p->onboarding_completed_at?->toIso8601String(),
            'onboarding_version' => $p->onboarding_version,
        ];
    }

    private function serializeDaily(?UserDailyStatus $d, ?UserProfile $profile): array
    {
        if (! $d instanceof UserDailyStatus) {
            return [
                'has_record' => false,
                'period_status' => self::PERIOD_NONE,
                'period_feature_enabled' => $profile?->period_feature_enabled ?? false,
                'flavor_tags' => [],
                'taboo_tags' => [],
            ];
        }

        return [
            'has_record' => true,
            'status_date' => $d->status_date->format('Y-m-d'),
            'mood' => $d->mood,
            'appetite_state' => $d->appetite_state,
            'body_state' => $d->body_state,
            'wanted_food_style' => $d->wanted_food_style,
            'flavor_tags' => array_values($d->flavor_tags ?? []),
            'taboo_tags' => array_values($d->taboo_tags ?? []),
            'period_status' => $d->period_status,
            'note' => $d->note,
            'period_feature_enabled' => $profile?->period_feature_enabled ?? false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSessionPreferences(array $pref): array
    {
        $people = $pref['people'] ?? null;
        $people = is_numeric($people) ? max(0, (int) $people) : null;

        return [
            'taste' => isset($pref['taste']) ? mb_substr(trim((string) $pref['taste']), 0, 200) : '',
            'avoid' => isset($pref['avoid']) ? mb_substr(trim((string) $pref['avoid']), 0, 400) : '',
            'people' => $people && $people > 0 ? $people : null,
        ];
    }

    /**
     * @return array{recent_titles: list<string>, recent_items: list<array<string, mixed>>}
     */
    private function buildHistoryContext(int $userId): array
    {
        /** @var Collection<int, RecipeHistory> $rows */
        $rows = RecipeHistory::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'title', 'cuisine', 'ingredients', 'request_payload', 'created_at']);

        $titles = [];
        $items = [];
        foreach ($rows as $row) {
            $titles[] = (string) $row->title;
            $items[] = [
                'title' => (string) $row->title,
                'cuisine' => $row->cuisine,
                'created_at' => $row->created_at?->toIso8601String(),
                'taste_hint' => is_array($row->request_payload) ? ($row->request_payload['taste'] ?? null) : null,
            ];
        }

        return [
            'recent_titles' => $titles,
            'recent_items' => $items,
        ];
    }
}
