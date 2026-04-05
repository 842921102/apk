<?php

namespace App\Services;

use App\Models\RecommendationRecord;
use App\Models\RecommendationSession;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * 将每次成功的今日推荐写入 recommendation_records，供最近推荐与分析。
 */
final class RecommendationRecordService
{
    /**
     * @param  array<string, mixed>  $aggregatedContext  RecommendationContextService::aggregateForUser 返回值
     * @param  array<string, mixed>  $result  DishReasonGenerator 输出（含 content 为菜谱正文）
     * @param  'initial'|'reroll'|'alternative_selected'  $source
     */
    public function persistFromTodayEat(
        User $user,
        RecommendationSession $session,
        array $aggregatedContext,
        array $result,
        string $source,
    ): RecommendationRecord {
        $date = Carbon::today();
        $profileModel = $user->profile;
        $usedDefaultProfile = $profileModel === null || $profileModel->onboarding_completed_at === null;

        return RecommendationRecord::query()->create([
            'user_id' => $user->id,
            'session_id' => $session->id,
            'recommendation_source' => $source,
            'recommendation_date' => $date->toDateString(),
            'meal_type' => 'dinner',
            'recommended_dish' => (string) ($result['recommended_dish'] ?? $result['title'] ?? ''),
            'tags' => $this->stringList($result['tags'] ?? []),
            'reason_text' => (string) ($result['reason_text'] ?? ''),
            'destiny_text' => isset($result['destiny_text']) ? (string) $result['destiny_text'] : null,
            'destiny_style' => isset($result['destiny_style']) && $result['destiny_style'] !== ''
                ? (string) $result['destiny_style']
                : null,
            'reason_style' => isset($result['reason_style']) && $result['reason_style'] !== ''
                ? (string) $result['reason_style']
                : null,
            'alternatives' => $this->stringList($result['alternatives'] ?? []),
            'cuisine' => isset($result['cuisine']) && $result['cuisine'] !== '' ? (string) $result['cuisine'] : null,
            'ingredients' => $this->stringList($result['ingredients'] ?? []),
            'recipe_content' => (string) ($result['content'] ?? ''),
            'weather_snapshot' => $this->jsonSnapshot($aggregatedContext['weather_context'] ?? null),
            'festival_snapshot' => $this->jsonSnapshot($aggregatedContext['festival_context'] ?? null),
            'user_profile_snapshot' => $this->jsonSnapshot($aggregatedContext['user_profile'] ?? null),
            'daily_status_snapshot' => $this->jsonSnapshot($aggregatedContext['daily_status'] ?? null),
            'is_favorited' => false,
            'recommendation_fallback' => (bool) ($result['recommendation_fallback'] ?? false),
            'used_default_profile' => $usedDefaultProfile,
        ]);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $x) {
            if (is_string($x) && trim($x) !== '') {
                $out[] = trim($x);
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jsonSnapshot(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        return $value;
    }
}
