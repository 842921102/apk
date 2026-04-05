<?php

namespace App\Services;

use App\Models\RecommendationEvent;
use App\Support\RecommendationEventType;
use Illuminate\Support\Carbon;

/**
 * 推荐效果指标（MVP：基于 recommendation_events 规则统计）。
 */
final class RecommendationAnalyticsService
{
    /**
     * @return array{
     *   recommendation_views: int,
     *   recipe_favorites_total: int,
     *   recipe_favorites_from_recommendation: int,
     *   recipe_views: int,
     *   recipe_views_from_recommendation: int,
     *   rerolls: int,
     *   select_alternatives: int,
     *   feedbacks: int,
     *   want_rate_pct: float|null,
     *   favorite_rate_pct: float|null,
     *   recipe_view_rate_pct: float|null,
     *   reroll_rate_pct: float|null,
     *   not_today_rate_pct: float|null,
     *   not_suitable_rate_pct: float|null,
     * }
     */
    public function todayOverview(Carbon $now): array
    {
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->addDay()->startOfDay();

        $views = $this->countTypeBetween(RecommendationEventType::RecommendationView, $start, $end);
        $favTotal = $this->countTypeBetween(RecommendationEventType::RecipeFavorite, $start, $end);
        $favRec = $this->countRecipeFavoriteFromRecommendationBetween($start, $end);
        $recipeViews = $this->countTypeBetween(RecommendationEventType::RecipeView, $start, $end);
        $recipeViewsRec = RecommendationEvent::query()
            ->where('event_type', RecommendationEventType::RecipeView->value)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('recommendation_record_id')
            ->count();
        $rerolls = $this->countTypeBetween(RecommendationEventType::RecommendationReroll, $start, $end);
        $selectAlt = $this->countTypeBetween(RecommendationEventType::RecommendationSelectAlternative, $start, $end);
        $feedbacks = $this->countTypeBetween(RecommendationEventType::RecommendationFeedback, $start, $end);

        $wants = $this->countTypeBetween(RecommendationEventType::RecommendationAccept, $start, $end);
        $notToday = $this->countFeedbackSubtypeBetween('not_today', $start, $end);
        $notSuitable = $this->countFeedbackSubtypeBetween('not_suitable', $start, $end);

        return [
            'recommendation_views' => $views,
            'recipe_favorites_total' => $favTotal,
            'recipe_favorites_from_recommendation' => $favRec,
            'recipe_views' => $recipeViews,
            'recipe_views_from_recommendation' => $recipeViewsRec,
            'rerolls' => $rerolls,
            'select_alternatives' => $selectAlt,
            'feedbacks' => $feedbacks,
            'want_rate_pct' => $this->ratePct($wants, $views),
            'favorite_rate_pct' => $this->ratePct($favRec, $views),
            'recipe_view_rate_pct' => $this->ratePct($recipeViewsRec, $views),
            'reroll_rate_pct' => $this->ratePct($rerolls, $views),
            'not_today_rate_pct' => $this->ratePct($notToday, $views),
            'not_suitable_rate_pct' => $this->ratePct($notSuitable, $views),
        ];
    }

    /**
     * @return list<array{
     *   date: string,
     *   recommendation_views: int,
     *   want_rate_pct: float|null,
     *   favorite_rate_pct: float|null,
     *   recipe_view_rate_pct: float|null,
     *   reroll_rate_pct: float|null,
     * }>
     */
    public function last7DaysTrend(Carbon $now): array
    {
        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->startOfDay()->subDays($i);
            $start = $day->copy();
            $end = $day->copy()->addDay();
            $views = $this->countTypeBetween(RecommendationEventType::RecommendationView, $start, $end);
            $wants = $this->countTypeBetween(RecommendationEventType::RecommendationAccept, $start, $end);
            $favRec = $this->countRecipeFavoriteFromRecommendationBetween($start, $end);
            $recipeViewsRec = RecommendationEvent::query()
                ->where('event_type', RecommendationEventType::RecipeView->value)
                ->whereBetween('created_at', [$start, $end])
                ->whereNotNull('recommendation_record_id')
                ->count();
            $rerolls = $this->countTypeBetween(RecommendationEventType::RecommendationReroll, $start, $end);

            $out[] = [
                'date' => $day->toDateString(),
                'recommendation_views' => $views,
                'want_rate_pct' => $this->ratePct($wants, $views),
                'favorite_rate_pct' => $this->ratePct($favRec, $views),
                'recipe_view_rate_pct' => $this->ratePct($recipeViewsRec, $views),
                'reroll_rate_pct' => $this->ratePct($rerolls, $views),
            ];
        }

        return $out;
    }

    /**
     * @return array{
     *   top_impression_tags: list<array{tag: string, count: int}>,
     *   top_favorite_tags: list<array{tag: string, count: int}>,
     *   top_reject_tags: list<array{tag: string, count: int}>,
     * }
     */
    public function tagPerformance(Carbon $now, int $days = 7): array
    {
        $since = $now->copy()->startOfDay()->subDays($days);

        $impression = $this->sumTagCounts(
            RecommendationEventType::RecommendationView,
            $since,
        );
        $favorite = $this->sumTagCounts(
            RecommendationEventType::RecipeFavorite,
            $since,
            fn (array $m): bool => ($m['favorite_source_type'] ?? '') === 'recommendation_record'
                || ($m['favorite_source_type'] ?? '') === 'today_eat',
        );
        $reject = $this->sumTagCounts(RecommendationEventType::RecommendationReject, $since);

        return [
            'top_impression_tags' => $this->topNKeyed($impression, 12, 'tag'),
            'top_favorite_tags' => $this->topNKeyed($favorite, 12, 'tag'),
            'top_reject_tags' => $this->topNKeyed($reject, 12, 'tag'),
        ];
    }

    /**
     * @return array{
     *   destiny_style: list<array{style: string, views: int, accepts: int, favorites: int, rejects: int}>,
     *   reason_style: list<array{style: string, views: int, accepts: int, favorites: int, rejects: int}>,
     * }
     */
    public function copyStylePerformance(Carbon $now, int $days = 7): array
    {
        $since = $now->copy()->startOfDay()->subDays($days);

        return [
            'destiny_style' => $this->styleTable($since, 'destiny_style'),
            'reason_style' => $this->styleTable($since, 'reason_style'),
        ];
    }

    /**
     * @return array{
     *   top_dishes_by_view: list<array{dish: string, count: int}>,
     *   top_dishes_skipped: list<array{dish: string, count: int}>,
     * }
     */
    public function dishPerformance(Carbon $now, int $days = 7): array
    {
        $since = $now->copy()->startOfDay()->subDays($days);
        $views = [];
        $skips = [];

        RecommendationEvent::query()
            ->where('created_at', '>=', $since)
            ->whereIn('event_type', [
                RecommendationEventType::RecommendationView->value,
                RecommendationEventType::RecommendationReroll->value,
                RecommendationEventType::RecommendationReject->value,
            ])
            ->orderBy('id')
            ->chunkById(2000, function ($chunk) use (&$views, &$skips): void {
                foreach ($chunk as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    if ($row->event_type === RecommendationEventType::RecommendationView->value) {
                        $d = (string) ($meta['recommended_dish'] ?? '');
                        if ($d !== '') {
                            $views[$d] = ($views[$d] ?? 0) + 1;
                        }
                    }
                    if ($row->event_type === RecommendationEventType::RecommendationReroll->value) {
                        $d = (string) ($meta['previous_recommended_dish'] ?? '');
                        if ($d !== '') {
                            $skips[$d] = ($skips[$d] ?? 0) + 1;
                        }
                    }
                    if ($row->event_type === RecommendationEventType::RecommendationReject->value) {
                        $d = (string) ($meta['recommended_dish'] ?? '');
                        if ($d !== '') {
                            $skips[$d] = ($skips[$d] ?? 0) + 1;
                        }
                    }
                }
            });

        return [
            'top_dishes_by_view' => $this->topNKeyed($views, 15, 'dish'),
            'top_dishes_skipped' => $this->topNKeyed($skips, 15, 'dish'),
        ];
    }

    private function countTypeBetween(RecommendationEventType $type, Carbon $start, Carbon $end): int
    {
        return RecommendationEvent::query()
            ->where('event_type', $type->value)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    private function countFeedbackSubtypeBetween(string $subtype, Carbon $start, Carbon $end): int
    {
        return RecommendationEvent::query()
            ->where('event_type', RecommendationEventType::RecommendationFeedback->value)
            ->where('event_value', $subtype)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->count();
    }

    private function countRecipeFavoriteFromRecommendationBetween(Carbon $start, Carbon $end): int
    {
        return RecommendationEvent::query()
            ->where('event_type', RecommendationEventType::RecipeFavorite->value)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->where(function ($q): void {
                $q->where('event_value', 'recommendation_record')
                    ->orWhere('event_value', 'today_eat')
                    ->orWhereNotNull('recommendation_record_id');
            })
            ->count();
    }

    private function ratePct(int $num, int $den): ?float
    {
        if ($den <= 0) {
            return null;
        }

        return round($num / $den * 100, 1);
    }

    /**
     * @param  callable(array): bool|null  $metaFilter
     * @return array<string, int>
     */
    private function sumTagCounts(RecommendationEventType $type, Carbon $since, ?callable $metaFilter = null): array
    {
        $counts = [];
        RecommendationEvent::query()
            ->where('event_type', $type->value)
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->chunkById(2000, function ($chunk) use (&$counts, $metaFilter): void {
                foreach ($chunk as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    if ($metaFilter !== null && ! $metaFilter($meta)) {
                        continue;
                    }
                    $tags = $meta['tags'] ?? [];
                    if (! is_array($tags)) {
                        continue;
                    }
                    foreach (array_slice($tags, 0, 12) as $t) {
                        $s = trim((string) $t);
                        if ($s === '') {
                            continue;
                        }
                        $counts[$s] = ($counts[$s] ?? 0) + 1;
                    }
                }
            });

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array<string, int|string>>
     */
    private function topNKeyed(array $counts, int $n, string $key): array
    {
        arsort($counts);
        $out = [];
        $i = 0;
        foreach ($counts as $k => $v) {
            if ($i >= $n) {
                break;
            }
            $out[] = [$key => (string) $k, 'count' => $v];
            $i++;
        }

        return $out;
    }

    /**
     * @return list<array{style: string, views: int, accepts: int, favorites: int, rejects: int}>
     */
    private function styleTable(Carbon $since, string $styleKey): array
    {
        $views = [];
        $accepts = [];
        $favorites = [];
        $rejects = [];

        $accumulate = function (string $eventType, array &$bucket) use ($since, $styleKey): void {
            RecommendationEvent::query()
                ->where('event_type', $eventType)
                ->where('created_at', '>=', $since)
                ->orderBy('id')
                ->chunkById(2000, function ($chunk) use (&$bucket, $styleKey): void {
                    foreach ($chunk as $row) {
                        $meta = is_array($row->meta) ? $row->meta : [];
                        $st = isset($meta[$styleKey]) ? trim((string) $meta[$styleKey]) : '';
                        if ($st === '') {
                            continue;
                        }
                        $bucket[$st] = ($bucket[$st] ?? 0) + 1;
                    }
                });
        };

        $accumulate(RecommendationEventType::RecommendationView->value, $views);
        $accumulate(RecommendationEventType::RecommendationAccept->value, $accepts);
        $accumulate(RecommendationEventType::RecipeFavorite->value, $favorites);
        $accumulate(RecommendationEventType::RecommendationReject->value, $rejects);

        $keys = array_unique(array_merge(
            array_keys($views),
            array_keys($accepts),
            array_keys($favorites),
            array_keys($rejects),
        ));
        sort($keys);
        $rows = [];
        foreach ($keys as $k) {
            $rows[] = [
                'style' => $k,
                'views' => $views[$k] ?? 0,
                'accepts' => $accepts[$k] ?? 0,
                'favorites' => $favorites[$k] ?? 0,
                'rejects' => $rejects[$k] ?? 0,
            ];
        }
        usort($rows, fn ($a, $b) => $b['views'] <=> $a['views']);

        return array_slice($rows, 0, 20);
    }
}
