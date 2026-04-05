<?php

namespace App\Services;

use App\Models\DishRecipe;
use App\Models\Favorite;
use App\Models\RecommendationEvent;
use App\Models\RecommendationRecord;
use App\Models\RecommendationSession;
use App\Support\FavoriteSourceType;
use App\Support\RecommendationEventType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 工作台 Dashboard：基于 recommendation_records / recommendation_events / favorites 的库内统计。
 */
final class AdminDashboardService
{
    /**
     * @return array{
     *   recommend_count: int,
     *   active_users: int,
     *   favorites_count: int,
     *   recipe_view_count: int,
     *   reroll_count: int,
     *   feedback_count: int,
     *   recommend_count_yesterday_same_window: int,
     *   active_users_yesterday_same_window: int,
     *   favorites_count_yesterday_same_window: int,
     *   recipe_view_count_yesterday_same_window: int,
     *   reroll_count_yesterday_same_window: int,
     *   feedback_count_yesterday_same_window: int,
     * }
     */
    public function overview(Carbon $now): array
    {
        [$tStart, $tEnd] = $this->todaySoFarWindow($now);
        [$yStart, $yEnd] = $this->yesterdaySameLengthWindow($now);

        return [
            'recommend_count' => $this->countRecommendationRecordsBetween($tStart, $tEnd, true),
            'active_users' => $this->distinctUsersRecommendationRecordsBetween($tStart, $tEnd, true),
            'favorites_count' => $this->countFavoritesBetween($tStart, $tEnd, true),
            'recipe_view_count' => $this->countEventsBetween(RecommendationEventType::RecipeView, $tStart, $tEnd, true),
            'reroll_count' => $this->countEventsBetween(RecommendationEventType::RecommendationReroll, $tStart, $tEnd, true),
            'feedback_count' => $this->countEventsBetween(RecommendationEventType::RecommendationFeedback, $tStart, $tEnd, true),
            'recommend_count_yesterday_same_window' => $this->countRecommendationRecordsBetween($yStart, $yEnd, true),
            'active_users_yesterday_same_window' => $this->distinctUsersRecommendationRecordsBetween($yStart, $yEnd, true),
            'favorites_count_yesterday_same_window' => $this->countFavoritesBetween($yStart, $yEnd, true),
            'recipe_view_count_yesterday_same_window' => $this->countEventsBetween(RecommendationEventType::RecipeView, $yStart, $yEnd, true),
            'reroll_count_yesterday_same_window' => $this->countEventsBetween(RecommendationEventType::RecommendationReroll, $yStart, $yEnd, true),
            'feedback_count_yesterday_same_window' => $this->countEventsBetween(RecommendationEventType::RecommendationFeedback, $yStart, $yEnd, true),
        ];
    }

    /**
     * @return array{
     *   recommend_trend: list<array{date: string, value: int}>,
     *   favorite_rate_trend: list<array{date: string, value: float|null}>,
     *   reroll_rate_trend: list<array{date: string, value: float|null}>,
     * }
     */
    public function trends(Carbon $now): array
    {
        $recommendTrend = [];
        $favoriteRateTrend = [];
        $rerollRateTrend = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->startOfDay()->subDays($i);
            $start = $day->copy();
            $isToday = $day->isSameDay($now);
            $end = $isToday ? $now->copy() : $day->copy()->addDay();
            $inclusiveEnd = $isToday;
            $dateStr = $day->toDateString();

            $rec = $this->countRecommendationRecordsBetween($start, $end, $inclusiveEnd);
            $fav = $this->countFavoritesBetween($start, $end, $inclusiveEnd);
            $reroll = $this->countEventsBetween(RecommendationEventType::RecommendationReroll, $start, $end, $inclusiveEnd);

            $recommendTrend[] = ['date' => $dateStr, 'value' => $rec];
            $favoriteRateTrend[] = [
                'date' => $dateStr,
                'value' => $rec > 0 ? round($fav / $rec, 4) : null,
            ];
            $rerollRateTrend[] = [
                'date' => $dateStr,
                'value' => $rec > 0 ? round($reroll / $rec, 4) : null,
            ];
        }

        return [
            'recommend_trend' => $recommendTrend,
            'favorite_rate_trend' => $favoriteRateTrend,
            'reroll_rate_trend' => $rerollRateTrend,
        ];
    }

    /**
     * @return array{
     *   top_dishes: list<array{dish_name: string, favorite_count: int, recipe_view_count: int, score: int}>,
     *   worst_dishes: list<array{dish_name: string, reroll_count: int}>,
     *   top_tags: list<array{tag_name: string, hit_count: int, favorite_count: int}>,
     *   style_stats: list<array{style_kind: string, style_name: string, use_count: int, favorite_rate: float|null}>,
     * }
     */
    public function rankings(Carbon $now): array
    {
        [$dayStart, $dayEnd] = $this->todaySoFarWindow($now);

        return [
            'top_dishes' => $this->topDishesToday($dayStart, $dayEnd),
            'worst_dishes' => $this->worstDishesToday($dayStart, $dayEnd),
            'top_tags' => $this->topTagsToday($dayStart, $dayEnd),
            'style_stats' => $this->styleStatsToday($dayStart, $dayEnd),
        ];
    }

    /**
     * @return array{
     *   fallback_count: int,
     *   error_count: int,
     *   no_recipe_count: int,
     *   default_profile_count: int,
     * }
     */
    public function health(Carbon $now): array
    {
        [$tStart, $tEnd] = $this->todaySoFarWindow($now);

        return [
            'fallback_count' => RecommendationRecord::query()
                ->where('recommendation_fallback', true)
                ->where('created_at', '>=', $tStart)
                ->where('created_at', '<=', $tEnd)
                ->count(),
            'error_count' => $this->countEventsBetween(RecommendationEventType::Error, $tStart, $tEnd, true),
            'no_recipe_count' => $this->countRecordsWithoutActiveRecipeBetween($tStart, $tEnd, true),
            'default_profile_count' => RecommendationRecord::query()
                ->where('used_default_profile', true)
                ->where('created_at', '>=', $tStart)
                ->where('created_at', '<=', $tEnd)
                ->count(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function todaySoFarWindow(Carbon $now): array
    {
        $start = $now->copy()->startOfDay();

        return [$start, $now->copy()];
    }

    /**
     * 与「今日已过时长」等长的昨日窗口，用于同比。
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function yesterdaySameLengthWindow(Carbon $now): array
    {
        $todayStart = $now->copy()->startOfDay();
        $seconds = max(0, $now->diffInSeconds($todayStart));
        $yStart = $todayStart->copy()->subDay();

        return [$yStart, $yStart->copy()->addSeconds($seconds)];
    }

    private function countRecommendationRecordsBetween(Carbon $start, Carbon $end, bool $endInclusive): int
    {
        $q = RecommendationRecord::query()->where('created_at', '>=', $start);
        if ($endInclusive) {
            $q->where('created_at', '<=', $end);
        } else {
            $q->where('created_at', '<', $end);
        }

        return $q->count();
    }

    private function distinctUsersRecommendationRecordsBetween(Carbon $start, Carbon $end, bool $endInclusive): int
    {
        $q = RecommendationRecord::query()->where('created_at', '>=', $start);
        if ($endInclusive) {
            $q->where('created_at', '<=', $end);
        } else {
            $q->where('created_at', '<', $end);
        }

        return (int) $q->distinct()->count('user_id');
    }

    private function countFavoritesBetween(Carbon $start, Carbon $end, bool $endInclusive): int
    {
        $q = Favorite::query()->where('created_at', '>=', $start);
        if ($endInclusive) {
            $q->where('created_at', '<=', $end);
        } else {
            $q->where('created_at', '<', $end);
        }

        return $q->count();
    }

    private function countEventsBetween(RecommendationEventType $type, Carbon $start, Carbon $end, bool $endInclusive): int
    {
        $q = RecommendationEvent::query()
            ->where('event_type', $type->value)
            ->where('created_at', '>=', $start);
        if ($endInclusive) {
            $q->where('created_at', '<=', $end);
        } else {
            $q->where('created_at', '<', $end);
        }

        return $q->count();
    }

    private function countRecordsWithoutActiveRecipeBetween(Carbon $start, Carbon $end, bool $endInclusive): int
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $q = RecommendationRecord::query()
                ->where('created_at', '>=', $start);
            if ($endInclusive) {
                $q->where('created_at', '<=', $end);
            } else {
                $q->where('created_at', '<', $end);
            }

            return (int) $q->whereRaw(
                'NOT EXISTS (SELECT 1 FROM dish_recipes dr WHERE dr.dish_key = LOWER(TRIM(recommendation_records.recommended_dish)) AND dr.is_active = 1)'
            )->count();
        }

        $q = RecommendationRecord::query()->where('created_at', '>=', $start);
        if ($endInclusive) {
            $q->where('created_at', '<=', $end);
        } else {
            $q->where('created_at', '<', $end);
        }

        $ids = $q->pluck('id', 'recommended_dish');

        $cnt = 0;
        foreach ($ids as $dish => $_id) {
            $key = RecommendationSession::dishKey((string) $dish);
            if ($key === '' || DishRecipe::activeIdForRecommendedDish((string) $dish) === null) {
                $cnt++;
            }
        }

        return $cnt;
    }

    /**
     * @return list<array{dish_name: string, favorite_count: int, recipe_view_count: int, score: int}>
     */
    private function topDishesToday(Carbon $dayStart, Carbon $dayEnd): array
    {
        $favByDish = Favorite::query()
            ->where('favorites.source_type', FavoriteSourceType::RecommendationRecord->value)
            ->whereBetween('favorites.created_at', [$dayStart, $dayEnd])
            ->join('recommendation_records', function ($join): void {
                $join->whereRaw('recommendation_records.id = favorites.source_id');
            })
            ->selectRaw('recommendation_records.recommended_dish as d, COUNT(*) as c')
            ->groupBy('recommendation_records.recommended_dish')
            ->pluck('c', 'd');

        $viewsByDish = RecommendationEvent::query()
            ->where('recommendation_events.event_type', RecommendationEventType::RecipeView->value)
            ->whereNotNull('recommendation_events.recommendation_record_id')
            ->whereBetween('recommendation_events.created_at', [$dayStart, $dayEnd])
            ->join('recommendation_records', 'recommendation_events.recommendation_record_id', '=', 'recommendation_records.id')
            ->selectRaw('recommendation_records.recommended_dish as d, COUNT(*) as c')
            ->groupBy('recommendation_records.recommended_dish')
            ->pluck('c', 'd');

        $names = array_unique(array_merge($favByDish->keys()->all(), $viewsByDish->keys()->all()));
        $rows = [];
        foreach ($names as $name) {
            if ($name === null || $name === '') {
                continue;
            }
            $fc = (int) ($favByDish[$name] ?? 0);
            $vc = (int) ($viewsByDish[$name] ?? 0);
            $rows[] = [
                'dish_name' => (string) $name,
                'favorite_count' => $fc,
                'recipe_view_count' => $vc,
                'score' => $fc + $vc,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return strcmp($a['dish_name'], $b['dish_name']);
        });

        return array_slice($rows, 0, 5);
    }

    /**
     * @return list<array{dish_name: string, reroll_count: int}>
     */
    private function worstDishesToday(Carbon $dayStart, Carbon $dayEnd): array
    {
        $counts = [];
        RecommendationEvent::query()
            ->where('event_type', RecommendationEventType::RecommendationReroll->value)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderBy('id')
            ->chunkById(2000, function ($chunk) use (&$counts): void {
                foreach ($chunk as $row) {
                    $meta = is_array($row->meta) ? $row->meta : [];
                    $d = isset($meta['previous_recommended_dish']) ? trim((string) $meta['previous_recommended_dish']) : '';
                    if ($d === '') {
                        continue;
                    }
                    $counts[$d] = ($counts[$d] ?? 0) + 1;
                }
            });

        arsort($counts);
        $out = [];
        $i = 0;
        foreach ($counts as $dish => $c) {
            if ($i >= 5) {
                break;
            }
            $out[] = ['dish_name' => $dish, 'reroll_count' => $c];
            $i++;
        }

        return $out;
    }

    /**
     * @return list<array{tag_name: string, hit_count: int, favorite_count: int}>
     */
    private function topTagsToday(Carbon $dayStart, Carbon $dayEnd): array
    {
        $hits = [];
        $favs = [];

        RecommendationRecord::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderBy('id')
            ->chunkById(1000, function ($chunk) use (&$hits, &$favs): void {
                foreach ($chunk as $r) {
                    $tags = is_array($r->tags) ? $r->tags : [];
                    foreach ($tags as $t) {
                        $s = trim((string) $t);
                        if ($s === '') {
                            continue;
                        }
                        $hits[$s] = ($hits[$s] ?? 0) + 1;
                        if ($r->is_favorited) {
                            $favs[$s] = ($favs[$s] ?? 0) + 1;
                        }
                    }
                }
            });

        arsort($hits);
        $out = [];
        $i = 0;
        foreach ($hits as $tag => $h) {
            if ($i >= 5) {
                break;
            }
            $out[] = [
                'tag_name' => $tag,
                'hit_count' => $h,
                'favorite_count' => (int) ($favs[$tag] ?? 0),
            ];
            $i++;
        }

        return $out;
    }

    /**
     * reason_style / destiny_style 分行返回，收藏率 = 当日该风格下 is_favorited 为 true 的记录数 / 该风格推荐次数。
     *
     * @return list<array{style_kind: string, style_name: string, use_count: int, favorite_rate: float|null}>
     */
    private function styleStatsToday(Carbon $dayStart, Carbon $dayEnd): array
    {
        $acc = [];

        RecommendationRecord::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->orderBy('id')
            ->chunkById(1000, function ($chunk) use (&$acc): void {
                foreach ($chunk as $r) {
                    foreach (['reason_style', 'destiny_style'] as $kind) {
                        $name = isset($r->{$kind}) ? trim((string) $r->{$kind}) : '';
                        if ($name === '') {
                            continue;
                        }
                        $key = $kind.'|'.$name;
                        if (! isset($acc[$key])) {
                            $acc[$key] = ['kind' => $kind, 'name' => $name, 'uses' => 0, 'fav' => 0];
                        }
                        $acc[$key]['uses']++;
                        if ($r->is_favorited) {
                            $acc[$key]['fav']++;
                        }
                    }
                }
            });

        $rows = [];
        foreach ($acc as $row) {
            $u = $row['uses'];
            $rows[] = [
                'style_kind' => $row['kind'],
                'style_name' => $row['name'],
                'use_count' => $u,
                'favorite_rate' => $u > 0 ? round($row['fav'] / $u, 4) : null,
            ];
        }

        usort($rows, fn (array $a, array $b): int => $b['use_count'] <=> $a['use_count']);

        return array_slice($rows, 0, 10);
    }
}
