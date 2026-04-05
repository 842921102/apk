<?php

namespace App\Services;

use App\Support\DestinyCopyStyle;

/**
 * 根据上下文选择食命文案风格，并产出给模型的可控参数（非模板拼接正文，避免僵化）。
 */
final class DestinyCopyStyleService
{
    /**
     * @param  list<string>  $systemTags
     * @return array{
     *   style: string,
     *   tone: string,
     *   destiny_min_chars: int,
     *   destiny_max_chars: int,
     *   avoid_rules: list<string>,
     *   system_lines: list<string>,
     *   user_lines: list<string>,
     *   for_context: array<string, mixed>
     * }
     */
    public function build(array $aggregatedContext, array $systemTags): array
    {
        $style = $this->pickStyle($aggregatedContext, $systemTags);
        $def = $this->definition($style);

        $avoid = [
            '禁止出现：五行、八字、犯冲、算命、转运、灵验、卦象、风水、冲煞等玄学断言。',
            '禁止写成空洞鸡汤或纯口号，也不要整句只描述天气（可把天气当作一个轻量引子）。',
            '必须带一点「今天和你有关」的专属感，可与今日主菜名或用户状态轻轻勾连，语气轻松自然。',
        ];

        $systemLines = [
            '【食命文案 destiny_text — 产品线风格】',
            '- 系统选定风格：'.$style->value.'（'.$def['label_cn'].'）',
            '- 语气：'.$def['tone'],
            '- 长度：约 '.$def['min_chars'].'～'.$def['max_chars'].' 个汉字（含标点），单行，勿换行。',
            '- 写作要点：'.$def['craft'],
        ];

        $userLines = [
            '',
            '【食命 destiny_text 必须遵守】',
            '- 严格按上述风格与长度输出；与 reason_text 不可雷同。',
            ...array_map(static fn (string $r): string => '- '.$r, $avoid),
            ...$def['extra_user_rules'],
        ];

        return [
            'style' => $style->value,
            'tone' => $def['tone'],
            'destiny_min_chars' => $def['min_chars'],
            'destiny_max_chars' => $def['max_chars'],
            'avoid_rules' => $avoid,
            'system_lines' => $systemLines,
            'user_lines' => $userLines,
            'for_context' => [
                'style' => $style->value,
                'tone' => $def['tone'],
                'destiny_min_chars' => $def['min_chars'],
                'destiny_max_chars' => $def['max_chars'],
            ],
        ];
    }

    /**
     * @param  list<string>  $systemTags
     */
    private function pickStyle(array $ctx, array $systemTags): DestinyCopyStyle
    {
        $date = is_array($ctx['date_context'] ?? null) ? $ctx['date_context'] : [];
        $weather = is_array($ctx['weather_context'] ?? null) ? $ctx['weather_context'] : [];
        $fest = is_array($ctx['festival_context'] ?? null) ? $ctx['festival_context'] : [];
        $special = is_array($ctx['user_special_context'] ?? null) ? $ctx['user_special_context'] : [];
        $daily = is_array($ctx['daily_status'] ?? null) ? $ctx['daily_status'] : [];
        $profile = is_array($ctx['user_profile'] ?? null) ? $ctx['user_profile'] : [];

        $scores = [
            DestinyCopyStyle::Ritual->value => 0,
            DestinyCopyStyle::Healing->value => 0,
            DestinyCopyStyle::DestinyLight->value => 0,
            DestinyCopyStyle::Daily->value => 35,
        ];

        if (! empty($special['is_birthday'])) {
            $scores[DestinyCopyStyle::Ritual->value] += 80;
        }
        if (! empty($fest['is_festival'])) {
            $scores[DestinyCopyStyle::Ritual->value] += 70;
        }
        if (! empty($date['is_weekend'])) {
            $scores[DestinyCopyStyle::Ritual->value] += 25;
        }
        if (! empty($fest['solar_term']) && is_array($fest['solar_term'])) {
            $scores[DestinyCopyStyle::Ritual->value] += 28;
        }
        foreach ($fest['special_day_tags'] ?? [] as $t) {
            if (is_string($t) && (str_contains($t, '仪式') || str_contains($t, '节日'))) {
                $scores[DestinyCopyStyle::Ritual->value] += 15;
                break;
            }
        }

        $mood = (string) ($daily['mood'] ?? '');
        if (in_array($mood, ['tired', 'low'], true)) {
            $scores[DestinyCopyStyle::Healing->value] += 65;
        }
        $want = (string) ($daily['wanted_food_style'] ?? '');
        if ($want === 'hot') {
            $scores[DestinyCopyStyle::Healing->value] += 45;
        }
        $body = (string) ($daily['body_state'] ?? '');
        if ($body === 'want_warm_food') {
            $scores[DestinyCopyStyle::Healing->value] += 50;
        }
        if (! empty($weather['available']) && (! empty($weather['is_precipitation']) || in_array($weather['weather_type'] ?? '', ['rainy', 'storm', 'snow'], true))) {
            $scores[DestinyCopyStyle::Healing->value] += 40;
        }

        $destinyOn = ! empty($profile['destiny_mode_enabled']);
        if ($destinyOn) {
            $scores[DestinyCopyStyle::DestinyLight->value] += 42;
            if (! empty($weather['available'])) {
                $scores[DestinyCopyStyle::DestinyLight->value] += 22;
            }
            if (! empty($fest['solar_term']) && is_array($fest['solar_term'])) {
                $scores[DestinyCopyStyle::DestinyLight->value] += 18;
            }
            if (! empty($daily['has_record'])) {
                $scores[DestinyCopyStyle::DestinyLight->value] += 15;
            }
            if (count($systemTags) >= 6) {
                $scores[DestinyCopyStyle::DestinyLight->value] += 10;
            }
        } else {
            unset($scores[DestinyCopyStyle::DestinyLight->value]);
        }

        $maxScore = max($scores);
        /** @var list<string> $candidates */
        $candidates = array_keys(array_filter(
            $scores,
            static fn (int $s): bool => $s === $maxScore
        ));
        $priority = [
            DestinyCopyStyle::Ritual->value,
            DestinyCopyStyle::Healing->value,
            DestinyCopyStyle::DestinyLight->value,
            DestinyCopyStyle::Daily->value,
        ];
        $topKey = DestinyCopyStyle::Daily->value;
        foreach ($priority as $key) {
            if (in_array($key, $candidates, true)) {
                $topKey = $key;
                break;
            }
        }

        return DestinyCopyStyle::from($topKey);
    }

    /**
     * @return array{label_cn: string, tone: string, min_chars: int, max_chars: int, craft: string, extra_user_rules: list<string>}
     */
    private function definition(DestinyCopyStyle $s): array
    {
        return match ($s) {
            DestinyCopyStyle::Healing => [
                'label_cn' => '治愈型',
                'tone' => '温柔、安抚、像被照顾到的一口热饭',
                'min_chars' => 18,
                'max_chars' => 40,
                'craft' => '弱化评判，强调「今天先把自己安顿好」；可轻轻点暖胃、休息、一口舒服，不要悲情或说教。',
                'extra_user_rules' => [
                    '适合疲惫、低落、雨天或想暖胃的语境；不要制造焦虑。',
                ],
            ],
            DestinyCopyStyle::Daily => [
                'label_cn' => '家常踏实型',
                'tone' => '平实、可信、像靠谱邻居劝你好好吃饭',
                'min_chars' => 16,
                'max_chars' => 36,
                'craft' => '强调「今天就这一餐，踏实吃完就很好」；可带一点小幽默，但不要油滑。',
                'extra_user_rules' => [
                    '适合工作日晚餐、家常菜、一人食或家庭餐的基调；避免夸张承诺。',
                ],
            ],
            DestinyCopyStyle::Ritual => [
                'label_cn' => '仪式感型',
                'tone' => '轻快、有一点庆祝感，但保持生活化',
                'min_chars' => 18,
                'max_chars' => 42,
                'craft' => '可呼应生日、节日、周末或节气，用「值得认真吃一口」而非迷信话术。',
                'extra_user_rules' => [
                    '若有生日/节日/周末，可轻量点名「今天不一样」；仍禁止玄学词汇。',
                ],
            ],
            DestinyCopyStyle::DestinyLight => [
                'label_cn' => '轻命理型（产品梗，非算命）',
                'tone' => '轻松俏皮，像今日小签，但本质是饮食陪伴',
                'min_chars' => 20,
                'max_chars' => 44,
                'craft' => '用「今日食运」「今日饭桌签」类产品语感，把天气、节气、状态当作趣味由头，落脚到「这口适合你」。',
                'extra_user_rules' => [
                    '用户已开启食命模式：可玩一点「专属感」比喻，但必须可被理解为饮食/心情建议，不能假称预测命运。',
                ],
            ],
        };
    }
}
