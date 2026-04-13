<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\RecommendationRecord;
use App\Models\RecommendationSession;
use App\Models\User;
use App\Support\FavoriteSourceType;
use App\Support\PreferenceSignalSource;
use App\Support\PreferenceSignalType;
use App\Support\RecommendationFeedbackType;
use Illuminate\Support\Carbon;

/**
 * 将业务行为写入 user_preference_signals（规则型 MVP，无复杂模型）。
 */
final class UserPreferenceSignalRecorder
{
    public function __construct(
        private readonly UserPreferenceSignalService $signals,
    ) {}

    public function recordRecommendationRecordFavorited(User $user, RecommendationRecord $record): void
    {
        $uid = (int) $user->id;
        $this->bumpDish($uid, $record->recommended_dish, 11.0, PreferenceSignalSource::Favorite);
        $this->bumpCuisineForDish($uid, $record->recommended_dish, $record->cuisine, 9.0, PreferenceSignalSource::Favorite);
        $this->bumpFlavorsFromTags($uid, $record->tags ?? [], 5.5, PreferenceSignalSource::Favorite, 5);
        $this->bumpIngredientsAsFlavorHints($uid, $record->ingredients ?? [], 4.0, PreferenceSignalSource::Favorite, 5);
    }

    /**
     * @param  array<string, mixed>  $validated  StoreFavoriteRequest
     */
    public function recordFavoriteCreated(User $user, Favorite $favorite, array $validated): void
    {
        $uid = (int) $user->id;
        $type = (string) ($validated['source_type'] ?? $favorite->source_type ?? '');

        if ($type === FavoriteSourceType::Recipe->value) {
            $this->bumpDish($uid, (string) $favorite->title, 10.0, PreferenceSignalSource::Favorite);
            $this->bumpCuisineForDish($uid, (string) $favorite->title, $favorite->cuisine, 8.0, PreferenceSignalSource::Favorite);
            $this->bumpIngredientsAsFlavorHints($uid, $favorite->ingredients ?? [], 4.5, PreferenceSignalSource::Favorite, 5);
            $extra = $favorite->extra_payload;
            if (is_array($extra) && isset($extra['tags']) && is_array($extra['tags'])) {
                $this->bumpFlavorsFromTags($uid, $extra['tags'], 4.0, PreferenceSignalSource::Favorite, 6);
            }

            return;
        }

        if ($type === FavoriteSourceType::RecommendationRecord->value
            || $type === FavoriteSourceType::TodayEat->value
            || $type === FavoriteSourceType::CustomWizard->value) {
            $this->bumpDish($uid, (string) $favorite->title, 10.0, PreferenceSignalSource::Favorite);
            $this->bumpCuisineForDish($uid, (string) $favorite->title, $favorite->cuisine, 7.5, PreferenceSignalSource::Favorite);
            $this->bumpIngredientsAsFlavorHints($uid, $favorite->ingredients ?? [], 3.5, PreferenceSignalSource::Favorite, 5);
        }
    }

    public function recordFeedback(User $user, RecommendationRecord $record, RecommendationFeedbackType $type, ?string $reason, Carbon $today): void
    {
        $uid = (int) $user->id;
        $src = PreferenceSignalSource::Feedback;

        match ($type) {
            RecommendationFeedbackType::WantToEat => $this->recordWantToEat($uid, $record, $src),
            RecommendationFeedbackType::NotToday => $this->recordNotToday($uid, $record, $today, $src),
            RecommendationFeedbackType::NotSuitable => $this->recordNotSuitable($uid, $record, $src),
            RecommendationFeedbackType::ChangeDirection => $this->recordChangeDirection($uid, $record, $src),
            default => null,
        };
    }

    /**
     * @param  list<string>  $excludedPastMains
     */
    public function recordAlternativeSelected(User $user, string $selectedDishName, ?string $previousMainDish, array $catalog): void
    {
        $uid = (int) $user->id;
        $src = PreferenceSignalSource::AlternativeSelect;

        $sel = UserPreferenceSignalService::findCatalogDishByName($catalog, $selectedDishName);
        if ($sel !== null) {
            $this->bumpDish($uid, (string) $sel['name'], 9.0, $src);
            $this->signals->bump($uid, PreferenceSignalType::Cuisine, $this->norm((string) ($sel['cuisine_type'] ?? '')), (string) ($sel['cuisine_type'] ?? ''), 6.5, $src);
            foreach (array_slice($sel['flavor_tags'] ?? [], 0, 5) as $t) {
                $this->signals->bump($uid, PreferenceSignalType::Flavor, $this->norm((string) $t), (string) $t, 5.0, $src);
            }
            foreach (array_slice($sel['health_tags'] ?? [], 0, 4) as $t) {
                $this->signals->bump($uid, PreferenceSignalType::HealthTag, $this->norm((string) $t), (string) $t, 3.5, $src);
            }
        } else {
            $this->bumpDish($uid, $selectedDishName, 7.0, $src);
        }

        if ($previousMainDish !== null && trim($previousMainDish) !== '') {
            $main = UserPreferenceSignalService::findCatalogDishByName($catalog, $previousMainDish);
            if ($main !== null && ! empty($main['cuisine_type'])) {
                $this->signals->bump(
                    $uid,
                    PreferenceSignalType::Cuisine,
                    $this->norm((string) $main['cuisine_type']),
                    (string) $main['cuisine_type'],
                    -4.5,
                    $src,
                );
            }
        }
    }

    private function recordWantToEat(int $uid, RecommendationRecord $record, string $src): void
    {
        $this->bumpDish($uid, $record->recommended_dish, 9.0, $src);
        $this->bumpCuisineForDish($uid, $record->recommended_dish, $record->cuisine, 6.5, $src);
        $this->bumpFlavorsFromTags($uid, $record->tags ?? [], 6.0, $src, 5);
        $this->bumpIngredientsAsFlavorHints($uid, $record->ingredients ?? [], 3.5, $src, 5);
    }

    private function recordNotToday(int $uid, RecommendationRecord $record, Carbon $today, string $src): void
    {
        if ($record->recommendation_date->toDateString() !== $today->toDateString()) {
            return;
        }
        $this->bumpDish($uid, $record->recommended_dish, -22.0, $src);
        $this->bumpCuisineForDish($uid, $record->recommended_dish, $record->cuisine, -10.0, $src);
        $this->bumpFlavorsFromTags($uid, $record->tags ?? [], -9.0, $src, 6);
        $dk = RecommendationSession::dishKey($record->recommended_dish);
        if ($dk !== '') {
            $this->signals->bump(
                $uid,
                PreferenceSignalType::MoodTag,
                'not_today:'.$today->toDateString().':'.$dk,
                $record->recommended_dish,
                -10.0,
                $src,
            );
        }
    }

    private function recordNotSuitable(int $uid, RecommendationRecord $record, string $src): void
    {
        $this->bumpDish($uid, $record->recommended_dish, -16.0, $src);
        $this->bumpCuisineForDish($uid, $record->recommended_dish, $record->cuisine, -11.0, $src);
        $this->bumpFlavorsFromTags($uid, $record->tags ?? [], -9.5, $src, 5);
        foreach (array_slice($record->ingredients ?? [], 0, 5) as $ing) {
            $this->signals->bump($uid, PreferenceSignalType::Flavor, $this->norm((string) $ing), (string) $ing, -6.0, $src);
        }
    }

    private function recordChangeDirection(int $uid, RecommendationRecord $record, string $src): void
    {
        $this->bumpDish($uid, $record->recommended_dish, -5.0, $src);
        $this->bumpCuisineForDish($uid, $record->recommended_dish, $record->cuisine, -3.5, $src);
    }

    private function bumpDish(int $uid, string $dishName, float $delta, string $source): void
    {
        $k = RecommendationSession::dishKey($dishName);
        if ($k === '') {
            return;
        }
        $this->signals->bump($uid, PreferenceSignalType::Dish, $k, $dishName, $delta, $source);
    }

    /**
     * 优先用菜品池中的 cuisine_type（与打分字段一致），否则退回记录里的展示菜系文案。
     */
    private function bumpCuisineForDish(int $uid, string $dishName, mixed $fallbackCuisine, float $delta, string $source): void
    {
        $catalog = config('recommendation_dish_catalog', []);
        $catalog = is_array($catalog) ? $catalog : [];
        $d = UserPreferenceSignalService::findCatalogDishByName($catalog, $dishName);
        if ($d !== null && ! empty($d['cuisine_type'])) {
            $ct = (string) $d['cuisine_type'];
            $this->signals->bump($uid, PreferenceSignalType::Cuisine, mb_strtolower($ct), $ct, $delta, $source);

            return;
        }
        $c = $fallbackCuisine;
        if ($c === null || trim((string) $c) === '') {
            return;
        }
        $this->signals->bump($uid, PreferenceSignalType::Cuisine, $this->norm((string) $c), (string) $c, $delta, $source);
    }

    /**
     * @param  list<mixed>  $tags
     */
    private function bumpFlavorsFromTags(int $uid, array $tags, float $perTag, string $source, int $max): void
    {
        foreach (array_slice($tags, 0, $max) as $t) {
            $s = $this->norm((string) $t);
            if (mb_strlen($s) < 2) {
                continue;
            }
            $this->signals->bump($uid, PreferenceSignalType::Flavor, $s, (string) $t, $perTag, $source);
        }
    }

    /**
     * @param  list<mixed>  $ingredients
     */
    private function bumpIngredientsAsFlavorHints(int $uid, array $ingredients, float $per, string $source, int $max): void
    {
        foreach (array_slice($ingredients, 0, $max) as $ing) {
            $s = $this->norm((string) $ing);
            if (mb_strlen($s) < 2) {
                continue;
            }
            $this->signals->bump($uid, PreferenceSignalType::Flavor, $s, (string) $ing, $per, $source);
        }
    }

    private function norm(string $s): string
    {
        return mb_strtolower(trim($s));
    }
}
