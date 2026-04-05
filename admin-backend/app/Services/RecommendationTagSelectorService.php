<?php

namespace App\Services;

use App\Models\RecommendationSession;

/**
 * 将推荐结果页标签收敛为固定来源、统一命名、分层优先级与去重后的 3～4 枚短标签。
 */
final class RecommendationTagSelectorService
{
    public const MIN_TAGS = 3;

    public const MAX_TAGS = 4;

    private int $tie = 0;

    /**
     * @param  array<string, mixed>  $aggregatedContext  RecommendationContextService::aggregateForUser
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @return list<string>
     */
    public function selectDisplayTags(
        array $aggregatedContext,
        string $recommendedDish,
        array $sessionPreferences = [],
    ): array {
        $this->tie = 0;
        /** @var list<array{label: string, tier: int, bucket: string, tie: int}> $candidates */
        $candidates = array_merge(
            $this->collectP1($aggregatedContext, $sessionPreferences),
            $this->collectP2($aggregatedContext),
            $this->collectP3($aggregatedContext, $recommendedDish, $sessionPreferences),
            $this->collectP4($aggregatedContext, $recommendedDish),
        );

        usort(
            $candidates,
            static fn (array $a, array $b): int => $a['tier'] <=> $b['tier'] ?: $a['tie'] <=> $b['tie'],
        );

        $picked = [];
        $seenLabels = [];
        $usedBuckets = [];
        foreach ($candidates as $c) {
            if (isset($usedBuckets[$c['bucket']])) {
                continue;
            }
            if (isset($seenLabels[$c['label']])) {
                continue;
            }
            $usedBuckets[$c['bucket']] = true;
            $seenLabels[$c['label']] = true;
            $picked[] = $c['label'];
            if (count($picked) >= self::MAX_TAGS) {
                break;
            }
        }

        return $this->padIfNeeded($picked, $usedBuckets, $aggregatedContext, $recommendedDish, $sessionPreferences);
    }

    /**
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @return list<array{label: string, tier: int, bucket: string, tie: int}>
     */
    private function collectP1(array $ctx, array $sessionPreferences): array
    {
        $out = [];
        $date = is_array($ctx['date_context'] ?? null) ? $ctx['date_context'] : [];
        $weather = is_array($ctx['weather_context'] ?? null) ? $ctx['weather_context'] : [];
        $fest = is_array($ctx['festival_context'] ?? null) ? $ctx['festival_context'] : [];
        $special = is_array($ctx['user_special_context'] ?? null) ? $ctx['user_special_context'] : [];
        $daily = is_array($ctx['daily_status'] ?? null) ? $ctx['daily_status'] : [];

        if (! empty($weather['available'])) {
            $precip = ! empty($weather['is_precipitation'])
                || in_array($weather['weather_type'] ?? '', ['rainy', 'storm', 'snow'], true);
            $temp = $weather['temperature'] ?? $weather['temp_c'] ?? null;
            if ($precip) {
                $out[] = $this->candidate('雨天暖胃', 1, 'env_weather');
            } elseif (is_numeric($temp) && (float) $temp >= 30) {
                $out[] = $this->candidate('清爽解暑', 1, 'env_weather');
            }
        }

        if (! empty($fest['is_festival']) && ! empty($fest['festival_name'])) {
            $out[] = $this->candidate('节日仪式感', 1, 'ritual_tone');
        }

        if (! empty($special['is_birthday'])) {
            $out[] = $this->candidate('生日值得吃点好的', 1, 'ritual_tone');
        }

        $st = $fest['solar_term'] ?? null;
        if (is_array($st) && ! empty($st['name'])) {
            $out[] = $this->candidate('节气时令感', 1, 'cal_solar');
        }
        if (is_array($st) && ($st['key'] ?? '') === 'dongzhi') {
            $out[] = $this->candidate('温补优先', 1, 'state_period_solar');
        }

        if (! empty($daily['has_record'])) {
            $mood = (string) ($daily['mood'] ?? '');
            $out = array_merge($out, match ($mood) {
                'tired' => [$this->candidate('疲惫回血', 1, 'state_mood')],
                'low' => [$this->candidate('温柔提振', 1, 'state_mood')],
                'stressed' => [$this->candidate('舒缓省心', 1, 'state_mood')],
                default => [],
            });

            $body = (string) ($daily['body_state'] ?? '');
            if ($body === 'low_appetite') {
                $out[] = $this->candidate('胃口一般', 1, 'state_appetite');
            }
            if ($body === 'greasy_tired') {
                $out[] = $this->candidate('解腻清爽', 1, 'state_reset');
            }
            if ($body === 'need_energy') {
                $out[] = $this->candidate('回血充电', 1, 'state_energy');
            }

            $want = (string) ($daily['wanted_food_style'] ?? '');
            if ($want === 'hot' || $body === 'want_warm_food') {
                $out[] = $this->candidate('想吃热的', 1, 'state_warm_intent');
            }

            $period = (string) ($daily['period_status'] ?? '');
            if (in_array($period, ['menstrual', 'premenstrual'], true)) {
                $out[] = $this->candidate('温补优先', 1, 'state_period_body');
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return list<array{label: string, tier: int, bucket: string, tie: int}>
     */
    private function collectP2(array $ctx): array
    {
        $out = [];
        $profile = is_array($ctx['user_profile'] ?? null) ? $ctx['user_profile'] : [];

        $blob = $this->profileGoalsBlob($profile);

        foreach (['减脂', '轻负担', '低脂', '控卡', '控脂'] as $kw) {
            if (str_contains($blob, $kw)) {
                $out[] = $this->candidate('轻负担优先', 2, 'goal_burden');
                break;
            }
        }

        foreach (['增肌', '高蛋白', '蛋白'] as $kw) {
            if (str_contains($blob, $kw)) {
                $out[] = $this->candidate('高蛋白友好', 2, 'goal_protein');
                break;
            }
        }

        if (str_contains($blob, '养胃')) {
            $out[] = $this->candidate('养胃友好', 2, 'goal_stomach');
        }

        if (str_contains($blob, '清淡')) {
            $out[] = $this->candidate('清淡目标', 2, 'goal_light');
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @return list<array{label: string, tier: int, bucket: string, tie: int}>
     */
    private function collectP3(array $ctx, string $dish, array $sessionPreferences): array
    {
        $out = [];
        $profile = is_array($ctx['user_profile'] ?? null) ? $ctx['user_profile'] : [];
        $row = $this->resolveCatalogRow($dish);
        $people = $sessionPreferences['people'] ?? null;
        $peopleN = is_numeric($people) ? (int) $people : null;

        if (($profile['cooking_frequency'] ?? '') === 'rarely') {
            $out[] = $this->candidate('快手友好', 3, 'dish_quick');
        }

        if ($row !== null) {
            if (($row['cooking_complexity'] ?? '') === 'quick') {
                $out[] = $this->candidate('快手友好', 3, 'dish_quick');
            }
            if (($row['suitable_scene'] ?? '') === 'solo' && ($peopleN === null || $peopleN <= 1)) {
                $out[] = $this->candidate('一人食友好', 3, 'dish_solo');
            }
            foreach ($row['flavor_tags'] ?? [] as $ft) {
                if (is_string($ft) && str_contains($ft, '下饭')) {
                    $out[] = $this->candidate('下饭型', 3, 'dish_fan');
                    break;
                }
            }
            if (($row['cuisine_type'] ?? '') === 'home') {
                $out[] = $this->candidate('家常治愈', 3, 'dish_home');
            }
        }

        $dishTrim = mb_strtolower($dish);
        foreach (['汤', '粥', '煲', '炖', '砂锅', '羹', '火锅'] as $kw) {
            if (str_contains($dishTrim, mb_strtolower($kw))) {
                $out[] = $this->candidate('汤类热食', 3, 'dish_soup');
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @return list<array{label: string, tier: int, bucket: string, tie: int}>
     */
    private function collectP4(array $ctx, string $dish): array
    {
        $out = [];
        $date = is_array($ctx['date_context'] ?? null) ? $ctx['date_context'] : [];
        $weather = is_array($ctx['weather_context'] ?? null) ? $ctx['weather_context'] : [];
        $fest = is_array($ctx['festival_context'] ?? null) ? $ctx['festival_context'] : [];
        $special = is_array($ctx['user_special_context'] ?? null) ? $ctx['user_special_context'] : [];
        $daily = is_array($ctx['daily_status'] ?? null) ? $ctx['daily_status'] : [];

        $row = $this->resolveCatalogRow($dish);
        $festDay = ! empty($fest['is_festival']) || ! empty($special['is_birthday']);
        if (! $festDay && ! empty($date['is_weekend'])) {
            $out[] = $this->candidate('仪式感一餐', 4, 'ritual_tone');
        }

        $coldEnv = ($date['season'] ?? '') === 'winter';
        if (! empty($weather['available'])) {
            $temp = $weather['temperature'] ?? $weather['temp_c'] ?? null;
            if (is_numeric($temp) && (float) $temp < 14) {
                $coldEnv = true;
            }
        }

        $wantsWarm = ! empty($daily['has_record'])
            && (((string) ($daily['wanted_food_style'] ?? '')) === 'hot'
                || ((string) ($daily['body_state'] ?? '')) === 'want_warm_food');

        $dishWarm = false;
        if ($row !== null) {
            foreach ($row['temperature_tags'] ?? [] as $t) {
                if ($t === 'warm' || $t === 'hot') {
                    $dishWarm = true;
                    break;
                }
            }
        } else {
            foreach (['汤', '粥', '煲', '炖', '砂锅', '热', '暖'] as $kw) {
                if (str_contains(mb_strtolower($dish), mb_strtolower($kw))) {
                    $dishWarm = true;
                    break;
                }
            }
        }

        if ($coldEnv && ($wantsWarm || $dishWarm)) {
            $out[] = $this->candidate('今日宜热食', 4, 'vibe_hot');
        }

        $out[] = $this->candidate('今天这口很稳', 4, 'vibe_stable');

        return $out;
    }

    /**
     * @param  array<string, bool>  $usedBuckets
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @return list<string>
     */
    private function padIfNeeded(
        array $picked,
        array $usedBuckets,
        array $aggregatedContext,
        string $recommendedDish,
        array $sessionPreferences,
    ): array {
        $fallbacks = [
            ['家常治愈', 'dish_home'],
            ['快手友好', 'dish_quick'],
            ['今天这口很稳', 'vibe_stable'],
        ];

        foreach ($fallbacks as [$label, $bucket]) {
            if (count($picked) >= self::MIN_TAGS) {
                break;
            }
            if (isset($usedBuckets[$bucket]) || in_array($label, $picked, true)) {
                continue;
            }
            $picked[] = $label;
            $usedBuckets[$bucket] = true;
        }

        /** @var list<string> $extra */
        $extra = [];
        if (count($picked) < self::MIN_TAGS) {
            $extra = $this->collectP3($aggregatedContext, $recommendedDish, $sessionPreferences);
        }
        foreach ($extra as $c) {
            if (count($picked) >= self::MIN_TAGS) {
                break;
            }
            if (in_array($c['label'], $picked, true)) {
                continue;
            }
            if (isset($usedBuckets[$c['bucket']])) {
                continue;
            }
            $picked[] = $c['label'];
            $usedBuckets[$c['bucket']] = true;
        }

        return array_values(array_slice($picked, 0, self::MAX_TAGS));
    }

    /**
     * @return array{label: string, tier: int, bucket: string, tie: int}
     */
    private function candidate(string $label, int $tier, string $bucket): array
    {
        return [
            'label' => $label,
            'tier' => $tier,
            'bucket' => $bucket,
            'tie' => $this->tie++,
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function profileGoalsBlob(array $profile): string
    {
        $parts = [];
        foreach ($profile['diet_goal'] ?? [] as $item) {
            if (is_string($item) && $item !== '') {
                $parts[] = $item;
            }
        }
        $hg = $profile['health_goal'] ?? null;
        if (is_string($hg) && $hg !== '') {
            $parts[] = $hg;
        }
        foreach ($profile['diet_preferences'] ?? [] as $item) {
            if (is_string($item) && $item !== '') {
                $parts[] = $item;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveCatalogRow(string $dish): ?array
    {
        $catalog = config('recommendation_dish_catalog', []);
        if (! is_array($catalog)) {
            return null;
        }

        $want = RecommendationSession::dishKey($dish);
        foreach ($catalog as $row) {
            if (! is_array($row) || empty($row['name'])) {
                continue;
            }
            if (RecommendationSession::dishKey((string) $row['name']) === $want) {
                return $row;
            }
        }
        foreach ($catalog as $row) {
            if (! is_array($row) || empty($row['name'])) {
                continue;
            }
            $name = (string) $row['name'];
            if (mb_strpos($dish, $name) !== false || mb_strpos($name, $dish) !== false) {
                return $row;
            }
        }

        return null;
    }
}
