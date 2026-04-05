<?php

namespace App\Services;

use App\Models\DishRecipe;
use App\Models\RecommendationEvent;
use App\Models\RecommendationRecord;
use App\Models\User;
use App\Support\RecommendationEventType;
use App\Support\RecommendationFeedbackType;

/**
 * 推荐与画像相关埋点写入 recommendation_events。
 */
final class RecommendationEventRecorder
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        User $user,
        RecommendationEventType $type,
        ?string $eventValue = null,
        ?int $recommendationRecordId = null,
        array $meta = [],
    ): void {
        RecommendationEvent::query()->create([
            'user_id' => (int) $user->id,
            'recommendation_record_id' => $recommendationRecordId,
            'event_type' => $type->value,
            'event_value' => $eventValue !== null && $eventValue !== '' ? mb_substr($eventValue, 0, 255) : null,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    public function recommendationView(User $user, RecommendationRecord $record, array $extraMeta = []): void
    {
        $this->record(
            $user,
            RecommendationEventType::RecommendationView,
            $record->recommendation_source,
            (int) $record->id,
            array_merge(self::metaFromRecommendationRecord($record), $extraMeta),
        );
    }

    public function recommendationReroll(
        User $user,
        RecommendationRecord $record,
        ?string $previousRecommendedDish,
        array $extraMeta = [],
    ): void {
        $meta = array_merge(
            self::metaFromRecommendationRecord($record),
            [
                'previous_recommended_dish' => $previousRecommendedDish,
            ],
            $extraMeta,
        );
        $this->record(
            $user,
            RecommendationEventType::RecommendationReroll,
            $record->recommendation_source,
            (int) $record->id,
            $meta,
        );
        $this->recommendationView($user, $record, []);
    }

    public function recommendationSelectAlternative(
        User $user,
        RecommendationRecord $record,
        string $selectedDish,
        ?string $previousMain,
        array $extraMeta = [],
    ): void {
        $meta = array_merge(
            self::metaFromRecommendationRecord($record),
            [
                'selected_alternative_dish' => $selectedDish,
                'previous_main_dish' => $previousMain,
            ],
            $extraMeta,
        );
        $this->record(
            $user,
            RecommendationEventType::RecommendationSelectAlternative,
            $selectedDish,
            (int) $record->id,
            $meta,
        );
        $this->recommendationView($user, $record, []);
    }

    public function recipeView(User $user, DishRecipe $recipe, ?RecommendationRecord $fromRecord = null): void
    {
        $meta = [
            'dish_recipe_id' => $recipe->id,
            'recipe_title' => $recipe->title,
        ];
        if ($fromRecord instanceof RecommendationRecord) {
            $meta = array_merge($meta, self::metaFromRecommendationRecord($fromRecord));
        }
        $this->record(
            $user,
            RecommendationEventType::RecipeView,
            (string) $recipe->id,
            $fromRecord?->id !== null ? (int) $fromRecord->id : null,
            $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $validated  StoreFavoriteRequest payload
     */
    public function recipeFavoriteFromFavoriteStore(User $user, array $validated): void
    {
        $sourceType = (string) ($validated['source_type'] ?? '');
        $meta = [
            'favorite_source_type' => $sourceType,
            'title' => (string) ($validated['title'] ?? ''),
        ];
        $recId = null;
        if (isset($validated['extra_payload']) && is_array($validated['extra_payload'])) {
            $e = $validated['extra_payload'];
            $meta['extra_payload_keys'] = array_keys($e);
            if (isset($e['recommendation_record_id']) && is_numeric($e['recommendation_record_id'])) {
                $recId = (int) $e['recommendation_record_id'];
            }
        }
        $this->record(
            $user,
            RecommendationEventType::RecipeFavorite,
            $sourceType,
            $recId,
            $meta,
        );
    }

    public function recipeFavoriteFromRecommendationRecord(User $user, RecommendationRecord $record): void
    {
        $this->record(
            $user,
            RecommendationEventType::RecipeFavorite,
            'recommendation_record',
            (int) $record->id,
            array_merge(self::metaFromRecommendationRecord($record), [
                'favorite_source_type' => 'recommendation_record',
            ]),
        );
    }

    public function feedback(User $user, RecommendationRecord $record, RecommendationFeedbackType $type, ?string $reason): void
    {
        $base = array_merge(self::metaFromRecommendationRecord($record), [
            'feedback_type' => $type->value,
            'feedback_reason' => $reason,
        ]);
        $this->record(
            $user,
            RecommendationEventType::RecommendationFeedback,
            $type->value,
            (int) $record->id,
            $base,
        );

        if ($type === RecommendationFeedbackType::WantToEat) {
            $this->record(
                $user,
                RecommendationEventType::RecommendationAccept,
                $type->value,
                (int) $record->id,
                $base,
            );
        }
        if (in_array($type, [
            RecommendationFeedbackType::NotToday,
            RecommendationFeedbackType::NotSuitable,
            RecommendationFeedbackType::ChangeDirection,
        ], true)) {
            $this->record(
                $user,
                RecommendationEventType::RecommendationReject,
                $type->value,
                (int) $record->id,
                $base,
            );
        }
    }

    public function onboardingCompleted(User $user, int $version): void
    {
        $this->record(
            $user,
            RecommendationEventType::OnboardingCompleted,
            (string) $version,
            null,
            ['onboarding_version' => $version],
        );
    }

    /**
     * @param  list<string>  $changedKeys
     */
    public function profilePreferenceUpdated(User $user, array $changedKeys): void
    {
        $this->record(
            $user,
            RecommendationEventType::ProfilePreferenceUpdated,
            null,
            null,
            ['changed_keys' => array_slice($changedKeys, 0, 40)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function metaFromRecommendationRecord(RecommendationRecord $r): array
    {
        return [
            'session_id' => $r->session_id,
            'recommended_dish' => $r->recommended_dish,
            'tags' => is_array($r->tags) ? $r->tags : [],
            'destiny_style' => $r->destiny_style,
            'reason_style' => $r->reason_style,
            'recommendation_source' => $r->recommendation_source,
        ];
    }
}
