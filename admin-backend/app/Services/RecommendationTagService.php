<?php

namespace App\Services;

use App\Support\UserDailyStatusMvp;
use Illuminate\Support\Str;

/**
 * 依据聚合上下文生成推荐标签（规则层）；供 AI 解释层消费，勿让模型臆造天气/节日。
 */
final class RecommendationTagService
{
    /**
     * @return list<string>
     */
    public function buildFromContext(array $ctx): array
    {
        $tags = [];

        $profile = is_array($ctx['user_profile'] ?? null) ? $ctx['user_profile'] : [];
        $daily = is_array($ctx['daily_status'] ?? null) ? $ctx['daily_status'] : [];

        foreach ($this->profileMachineTags($profile) as $t) {
            $tags[] = $t;
        }
        foreach ($this->dailyMachineTags($daily, $profile) as $t) {
            $tags[] = $t;
        }

        foreach ($this->dailyMvpRuleLabels($daily) as $t) {
            $tags[] = $t;
        }

        foreach ($this->contextRuleLabels($ctx) as $t) {
            $tags[] = $t;
        }

        return array_values(array_unique(array_filter(array_map(static fn (string $s): string => trim($s), $tags), static fn (string $s): bool => $s !== '')));
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<string>
     */
    private function profileMachineTags(array $profile): array
    {
        $tags = [];

        if (! empty($profile['destiny_mode_enabled'])) {
            $tags[] = 'mode:destiny_cooking';
        }
        if (! empty($profile['recommendation_style'])) {
            $tags[] = 'style:'.(string) $profile['recommendation_style'];
        }

        foreach (['flavor_preferences', 'taboo_ingredients', 'diet_preferences', 'dislike_ingredients', 'allergy_ingredients', 'cuisine_preferences', 'lifestyle_tags', 'religious_restrictions'] as $key) {
            $prefix = match ($key) {
                'flavor_preferences' => 'flavor',
                'taboo_ingredients' => 'taboo',
                'diet_preferences' => 'diet',
                'dislike_ingredients' => 'dislike',
                'allergy_ingredients' => 'allergy',
                'cuisine_preferences' => 'cuisine',
                'lifestyle_tags' => 'lifestyle',
                'religious_restrictions' => 'religious',
                default => 'x',
            };
            $this->pushArray($tags, $prefix, $profile[$key] ?? null);
        }

        $goals = $profile['diet_goal'] ?? [];
        if (is_array($goals)) {
            foreach ($goals as $item) {
                $s = is_string($item) ? trim($item) : '';
                if ($s !== '') {
                    $tags[] = 'goal:'.$s;
                    if (Str::contains($s, ['减脂', '轻负担', '低脂', '控卡', '清淡饮食'])) {
                        $tags[] = '情境：轻负担优先';
                    }
                }
            }
        }

        if (! empty($profile['health_goal']) && (! is_array($goals) || $goals === [])) {
            $tags[] = 'goal:'.(string) $profile['health_goal'];
        }

        return $tags;
    }

    /**
     * @param  array<string, mixed>  $daily
     * @param  array<string, mixed>  $profile
     * @return list<string>
     */
    private function dailyMachineTags(array $daily, array $profile): array
    {
        $tags = [];
        foreach (['mood', 'appetite_state', 'body_state', 'wanted_food_style'] as $k) {
            if (! empty($daily[$k])) {
                $p = match ($k) {
                    'wanted_food_style' => 'want',
                    default => explode('_', $k, 2)[0],
                };
                $tags[] = $p.':'.(string) $daily[$k];
            }
        }
        $this->pushArray($tags, 'meal_flavor', $daily['flavor_tags'] ?? null);
        $taboos = $daily['taboo_tags'] ?? null;
        if (is_array($taboos) && ! in_array('none', $taboos, true)) {
            $this->pushArray($tags, 'meal_taboo', $taboos);
        }
        $periodOn = ! empty($profile['period_feature_enabled']) || ! empty($daily['period_feature_enabled']);
        if ($periodOn && ! empty($daily['period_status']) && (string) $daily['period_status'] !== 'none') {
            $tags[] = 'special_period:'.(string) $daily['period_status'];
        }

        return $tags;
    }

    /**
     * 今日状态 MVP：面向用户的短标签（与枚举一一对应，供推荐解释引用）。
     *
     * @param  array<string, mixed>  $daily
     * @return list<string>
     */
    private function dailyMvpRuleLabels(array $daily): array
    {
        if (empty($daily['has_record'])) {
            return [];
        }

        $out = [];
        $push = static function (array &$target, ?string $label): void {
            if (is_string($label) && $label !== '') {
                $target[] = $label;
            }
        };

        $mood = (string) ($daily['mood'] ?? '');
        $push($out, match ($mood) {
            'tired' => '情境：疲惫回血',
            'stressed' => '情境：舒缓省心',
            'low' => '情境：温柔提振',
            'happy' => '情境：轻松愉悦',
            'calm' => '情境：平和家常',
            default => null,
        });

        $want = (string) ($daily['wanted_food_style'] ?? '');
        $push($out, match ($want) {
            'hot' => '情境：暖胃热食',
            'light' => '情境：清爽适口',
            'comforting' => '情境：暖心安慰',
            'spicy' => '情境：微辣开胃',
            'quick' => '情境：快手省事',
            default => null,
        });

        $body = (string) ($daily['body_state'] ?? '');
        $push($out, match ($body) {
            'low_appetite' => '情境：清淡开胃',
            'want_warm_food' => '情境：暖胃取向',
            'greasy_tired' => '情境：解腻清爽',
            'need_energy' => '情境：补充能量',
            'normal' => '情境：状态平稳',
            default => null,
        });

        $period = (string) ($daily['period_status'] ?? '');
        $push($out, match ($period) {
            'menstrual' => '情境：温补优先',
            'premenstrual' => '情境：温和调养',
            'postmenstrual' => '情境：营养均衡',
            default => null,
        });

        $flavors = $daily['flavor_tags'] ?? [];
        if (is_array($flavors)) {
            foreach ($flavors as $fk) {
                $fk = is_string($fk) ? $fk : '';
                if ($fk !== '' && in_array($fk, UserDailyStatusMvp::flavorTagKeys(), true)) {
                    $out[] = '口味：'.UserDailyStatusMvp::flavorTagLabel($fk);
                }
            }
        }

        $taboos = $daily['taboo_tags'] ?? [];
        if (is_array($taboos) && ! in_array('none', $taboos, true)) {
            foreach ($taboos as $tk) {
                $tk = is_string($tk) ? $tk : '';
                if ($tk !== '' && $tk !== 'none' && in_array($tk, UserDailyStatusMvp::tabooTagKeys(), true)) {
                    $out[] = '忌口：'.UserDailyStatusMvp::tabooTagLabel($tk);
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $tags
     */
    private function pushArray(array &$tags, string $prefix, mixed $items): void
    {
        if (! is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            $s = is_string($item) ? trim($item) : '';
            if ($s !== '') {
                $tags[] = $prefix.':'.$s;
            }
        }
    }

    /**
     * @return list<string>
     */
    private function contextRuleLabels(array $ctx): array
    {
        $out = [];
        $dateCtx = is_array($ctx['date_context'] ?? null) ? $ctx['date_context'] : [];
        if (! empty($dateCtx['is_weekend'])) {
            $out[] = '时间：周末';
        }
        $meal = (string) ($dateCtx['meal_period'] ?? '');
        $out = array_merge($out, match ($meal) {
            'breakfast' => ['用餐：早餐时段'],
            'lunch' => ['用餐：午餐时段'],
            'dinner' => ['用餐：晚餐时段'],
            'late_night', 'snack' => ['用餐：夜宵/小食'],
            default => [],
        });
        $season = (string) ($dateCtx['season'] ?? '');
        if ($season === 'summer') {
            $out[] = '季节：夏季';
        } elseif ($season === 'winter') {
            $out[] = '季节：冬季';
        }

        $weather = is_array($ctx['weather_context'] ?? null) ? $ctx['weather_context'] : [];
        if (! empty($weather['available'])) {
            $rain = ! empty($weather['is_precipitation']) || ($weather['weather_type'] ?? '') === 'rainy' || ($weather['weather_type'] ?? '') === 'storm';
            $temp = $weather['temperature'] ?? $weather['temp_c'] ?? null;
            $cold = is_numeric($temp) && (float) $temp < 14;
            $feels = $weather['feels_like'] ?? null;
            if (is_numeric($feels) && (float) $feels < 12) {
                $cold = true;
            }
            if ($rain && $cold) {
                $out[] = '情境：雨天暖胃';
            } elseif ($rain) {
                $out[] = '情境：雨天适口';
            } elseif (is_numeric($temp) && (float) $temp >= 30) {
                $out[] = '情境：清爽解暑';
            } elseif (($weather['weather_type'] ?? '') === 'sunny' && is_numeric($temp) && (float) $temp >= 28) {
                $out[] = '情境：晴热宜清淡';
            }
            foreach ($weather['weather_tags'] ?? [] as $wt) {
                if (is_string($wt) && $wt !== '') {
                    $out[] = '天气：'.$wt;
                }
            }
        }

        $fest = is_array($ctx['festival_context'] ?? null) ? $ctx['festival_context'] : [];
        if (! empty($fest['is_festival']) && ! empty($fest['festival_name'])) {
            $out[] = '节日：'.(string) $fest['festival_name'];
            $out[] = '情境：仪式感推荐';
        }
        $st = $fest['solar_term'] ?? null;
        if (is_array($st) && ! empty($st['name'])) {
            $out[] = '节气：'.(string) $st['name'].'（非节日）';
            if (($st['key'] ?? '') === 'dongzhi') {
                $out[] = '情境：冬至温补优先';
            }
        }
        foreach ($fest['special_day_tags'] ?? [] as $t) {
            if (is_string($t) && $t !== '' && ! str_starts_with($t, '节气：')) {
                $out[] = '特殊日：'.$t;
            }
        }
        foreach ($fest['solar_calendar_events'] ?? [] as $ev) {
            if (is_array($ev) && ! empty($ev['label'])) {
                $out[] = '节日：'.(string) $ev['label'];
            }
        }

        $special = is_array($ctx['user_special_context'] ?? null) ? $ctx['user_special_context'] : [];
        if (! empty($special['is_birthday'])) {
            $out[] = '情境：生日值得吃点好的';
            $out[] = '情境：生日仪式感';
        }
        foreach ($special['user_special_tags'] ?? [] as $t) {
            if (is_string($t) && $t !== '' && ! str_contains($t, '生日年龄')) {
                $out[] = '用户特殊：'.$t;
            }
        }

        $profile = is_array($ctx['user_profile'] ?? null) ? $ctx['user_profile'] : [];
        if (($profile['cooking_frequency'] ?? '') === 'rarely') {
            $out[] = '情境：快手优先';
        }

        $hist = is_array($ctx['history_context'] ?? null) ? $ctx['history_context'] : [];
        $recent = $hist['recent_titles'] ?? [];
        if (is_array($recent) && $this->recentTitlesSeemHeavy(array_map('strval', $recent))) {
            $out[] = '情境：清爽修复';
        }

        return $out;
    }

    /**
     * @param  list<string>  $titles
     */
    private function recentTitlesSeemHeavy(array $titles): bool
    {
        if ($titles === []) {
            return false;
        }
        $heavy = ['辣', '麻', '火锅', '烧烤', '重口', '油炸', '香锅', '干锅'];
        $hit = 0;
        foreach (array_slice($titles, 0, 8) as $t) {
            foreach ($heavy as $h) {
                if (str_contains($t, $h)) {
                    $hit++;
                    break;
                }
            }
        }

        return $hit >= 3;
    }
}
