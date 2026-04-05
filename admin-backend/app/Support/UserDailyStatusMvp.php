<?php

namespace App\Support;

/**
 * 今日状态 MVP 枚举（与小程序「今日状态」弹层一致）。
 */
final class UserDailyStatusMvp
{
    /**
     * @return list<string>
     */
    public static function moods(): array
    {
        return ['tired', 'happy', 'calm', 'stressed', 'low'];
    }

    /**
     * @return list<string>
     */
    public static function wantedFoodStyles(): array
    {
        return ['hot', 'light', 'comforting', 'spicy', 'quick'];
    }

    /**
     * @return list<string>
     */
    public static function bodyStates(): array
    {
        return ['normal', 'low_appetite', 'want_warm_food', 'greasy_tired', 'need_energy'];
    }

    /**
     * @return list<string>
     */
    public static function periodStatuses(): array
    {
        return ['none', 'menstrual', 'premenstrual', 'postmenstrual', 'unknown'];
    }

    /**
     * 今日口味标签（小程序多选，写入 user_daily_statuses.flavor_tags）
     *
     * @return list<string>
     */
    public static function flavorTagKeys(): array
    {
        return ['light', 'spicy_hot', 'mild_spicy', 'sweet_sour', 'home_style', 'strong', 'soup'];
    }

    /**
     * 今日忌口标签（含 none=暂无，与其它项互斥）
     *
     * @return list<string>
     */
    public static function tabooTagKeys(): array
    {
        return ['coriander', 'alliums', 'seafood', 'organ', 'peanut', 'none'];
    }

    public static function flavorTagLabel(string $key): string
    {
        return match ($key) {
            'light' => '清淡',
            'spicy_hot' => '香辣',
            'mild_spicy' => '微辣',
            'sweet_sour' => '酸甜',
            'home_style' => '家常',
            'strong' => '重口',
            'soup' => '汤水',
            default => $key,
        };
    }

    public static function tabooTagLabel(string $key): string
    {
        return match ($key) {
            'coriander' => '香菜',
            'alliums' => '葱姜蒜',
            'seafood' => '海鲜',
            'organ' => '内脏',
            'peanut' => '花生',
            'none' => '暂无',
            default => $key,
        };
    }

    /**
     * @param  mixed  $tags
     */
    public static function flavorTagsToPreferenceString($tags): string
    {
        if (! is_array($tags) || $tags === []) {
            return '';
        }
        $labels = [];
        foreach ($tags as $t) {
            $k = is_string($t) ? $t : '';
            if ($k !== '' && in_array($k, self::flavorTagKeys(), true)) {
                $labels[] = self::flavorTagLabel($k);
            }
        }

        return implode('、', $labels);
    }

    /**
     * @param  mixed  $tags
     */
    public static function tabooTagsToPreferenceString($tags): string
    {
        if (! is_array($tags) || $tags === []) {
            return '';
        }
        if (in_array('none', $tags, true)) {
            return '';
        }
        $labels = [];
        foreach ($tags as $t) {
            $k = is_string($t) ? $t : '';
            if ($k !== '' && $k !== 'none' && in_array($k, self::tabooTagKeys(), true)) {
                $labels[] = self::tabooTagLabel($k);
            }
        }

        return implode('、', $labels);
    }

    /**
     * 合并请求里的 preferences 与当日状态中的口味/忌口，供推荐文案与模型提示使用。
     *
     * @param  array<string, mixed>  $requestPreferences
     * @param  array<string, mixed>  $aggregatedCtx  aggregateForUser 返回值
     * @return array{taste: string, avoid: string, people: int|null}
     */
    public static function normalizedSessionPreferencesFromContext(array $requestPreferences, array $aggregatedCtx): array
    {
        $daily = is_array($aggregatedCtx['daily_status'] ?? null) ? $aggregatedCtx['daily_status'] : [];
        $tasteReq = isset($requestPreferences['taste']) ? trim((string) $requestPreferences['taste']) : '';
        $avoidReq = isset($requestPreferences['avoid']) ? trim((string) $requestPreferences['avoid']) : '';
        $peopleRaw = $requestPreferences['people'] ?? null;
        $people = is_numeric($peopleRaw) ? max(0, (int) $peopleRaw) : null;

        $tasteDaily = self::flavorTagsToPreferenceString($daily['flavor_tags'] ?? null);
        $avoidDaily = self::tabooTagsToPreferenceString($daily['taboo_tags'] ?? null);

        return [
            'taste' => $tasteDaily !== '' ? $tasteDaily : $tasteReq,
            'avoid' => $avoidDaily !== '' ? $avoidDaily : $avoidReq,
            'people' => $people && $people > 0 ? $people : null,
        ];
    }
}
