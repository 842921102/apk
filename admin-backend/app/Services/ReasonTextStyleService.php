<?php

namespace App\Services;

use App\Support\ReasonTextStyle;

/**
 * 根据上下文选择推荐理由风格，并产出给模型的结构与边界参数。
 */
final class ReasonTextStyleService
{
    /**
     * @param  list<string>  $systemTags
     * @return array{
     *   style: string,
     *   tone: string,
     *   reason_min_chars: int,
     *   reason_max_chars: int,
     *   must_include: list<string>,
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

        $mustInclude = [
            '先一句点出「今日外部情境」：仅从上下文已给出的日期/工作日或周末、天气摘要、节日或节气、生日等中择 1～2 点，不得臆造。',
            '再一句衔接「用户状态或目标」：画像里的饮食目标/忌口相关、今日状态、表单口味或人数等，择 1～2 点，要具体可核对。',
            '最后 1～2 句落到「为何这道菜更贴今天」：结合菜名、味型、烹饪复杂度、暖胃/清爽等可感知特征，形成因果链。',
        ];

        $avoid = [
            '禁止玄学、算命式断言与恐吓式措辞。',
            '禁止空洞鸡汤、口号式排比、以及「你就是最棒的」类泛化安慰。',
            '禁止把 reason_text 写成 destiny_text：不要签文体、不要押韵对联式金句；整段应以说明与逻辑为主。',
            '禁止与 destiny_text 复用相同比喻或同一句式；reason 偏理性解释，destiny 偏轻情绪，二者职责不得互换。',
        ];

        $systemLines = [
            '【推荐理由 reason_text — 产品线风格】',
            '- 系统选定风格：'.$style->value.'（'.$def['label_cn'].'）',
            '- 语气：'.$def['tone'],
            '- 建议长度：约 '.$def['min_chars'].'～'.$def['max_chars'].' 字（含标点），一段连续文本，少用分点符号。',
            '- 半结构逻辑（须自然揉进段落，不要列「第一第二」）：',
            '  1) 外部情境（天气/时间/节日等）',
            '  2) 用户状态或目标（状态/目标/忌口/人数等）',
            '  3) 菜品特征如何匹配以上两点',
            '- 写作要点：'.$def['craft'],
        ];

        $userLines = [
            '',
            '【推荐理由 reason_text 必须遵守】',
            '- 严格按上述风格与建议长度输出。',
            ...array_map(static fn (string $r): string => '- 必须：'.$r, $mustInclude),
            ...array_map(static fn (string $r): string => '- '.$r, $avoid),
            ...array_map(static fn (string $r): string => '- 侧重：'.$r, $def['extra_must']),
        ];

        return [
            'style' => $style->value,
            'tone' => $def['tone'],
            'reason_min_chars' => $def['min_chars'],
            'reason_max_chars' => $def['max_chars'],
            'must_include' => $mustInclude,
            'avoid_rules' => $avoid,
            'system_lines' => $systemLines,
            'user_lines' => $userLines,
            'for_context' => [
                'style' => $style->value,
                'tone' => $def['tone'],
                'reason_min_chars' => $def['min_chars'],
                'reason_max_chars' => $def['max_chars'],
                'structure' => 'context_then_user_then_dish',
            ],
        ];
    }

    /**
     * @param  list<string>  $systemTags
     */
    private function pickStyle(array $ctx, array $systemTags): ReasonTextStyle
    {
        $date = is_array($ctx['date_context'] ?? null) ? $ctx['date_context'] : [];
        $weather = is_array($ctx['weather_context'] ?? null) ? $ctx['weather_context'] : [];
        $daily = is_array($ctx['daily_status'] ?? null) ? $ctx['daily_status'] : [];
        $profile = is_array($ctx['user_profile'] ?? null) ? $ctx['user_profile'] : [];
        $session = is_array($ctx['session_preferences'] ?? null) ? $ctx['session_preferences'] : [];

        $scores = [
            ReasonTextStyle::Practical->value => 26,
            ReasonTextStyle::Caring->value => 0,
            ReasonTextStyle::GoalOriented->value => 0,
            ReasonTextStyle::SceneBased->value => 0,
        ];

        if ($this->profileHasGoalSignals($profile)) {
            $scores[ReasonTextStyle::GoalOriented->value] += 88;
        }

        if ($this->sceneSignals($profile, $session, $systemTags, $date)) {
            $scores[ReasonTextStyle::SceneBased->value] += 72;
        }

        $mood = (string) ($daily['mood'] ?? '');
        if (in_array($mood, ['tired', 'low', 'stressed'], true)) {
            $scores[ReasonTextStyle::Caring->value] += 68;
        }
        $body = (string) ($daily['body_state'] ?? '');
        if (in_array($body, ['want_warm_food', 'low_appetite', 'need_energy'], true)) {
            $scores[ReasonTextStyle::Caring->value] += 52;
        }
        $want = (string) ($daily['wanted_food_style'] ?? '');
        if (in_array($want, ['hot', 'light', 'comforting'], true)) {
            $scores[ReasonTextStyle::Caring->value] += 42;
        }
        $period = (string) ($daily['period_status'] ?? '');
        if (in_array($period, ['menstrual', 'premenstrual'], true)) {
            $scores[ReasonTextStyle::Caring->value] += 38;
        }
        if (! empty($weather['available']) && (! empty($weather['is_precipitation']) || in_array($weather['weather_type'] ?? '', ['rainy', 'storm', 'snow'], true))) {
            $scores[ReasonTextStyle::Caring->value] += 28;
        }

        $maxScore = max($scores);
        /** @var list<string> $candidates */
        $candidates = array_keys(array_filter(
            $scores,
            static fn (int $s): bool => $s === $maxScore
        ));
        $priority = [
            ReasonTextStyle::Caring->value,
            ReasonTextStyle::GoalOriented->value,
            ReasonTextStyle::SceneBased->value,
            ReasonTextStyle::Practical->value,
        ];
        $topKey = ReasonTextStyle::Practical->value;
        foreach ($priority as $key) {
            if (in_array($key, $candidates, true)) {
                $topKey = $key;
                break;
            }
        }

        return ReasonTextStyle::from($topKey);
    }

    /**
     * @return array{label_cn: string, tone: string, min_chars: int, max_chars: int, craft: string, extra_must: list<string>}
     */
    private function definition(ReasonTextStyle $s): array
    {
        return match ($s) {
            ReasonTextStyle::Practical => [
                'label_cn' => '实用说明型',
                'tone' => '清楚、务实，像把「匹配关系」一条条说清楚',
                'min_chars' => 100,
                'max_chars' => 200,
                'craft' => '优先把天气/日期与菜品属性对齐；少形容词堆砌，多可核对的因果。',
                'extra_must' => [
                    '至少引用两类不同线索（例如天气+状态，或节日+忌口），避免只谈菜好吃。',
                ],
            ],
            ReasonTextStyle::Caring => [
                'label_cn' => '贴心照顾型',
                'tone' => '体贴、安定，但仍是解释而不是哄劝口号',
                'min_chars' => 110,
                'max_chars' => 210,
                'craft' => '承认用户今天可能累了或想暖胃/想清淡，再落到菜上；避免说教与悲情。',
                'extra_must' => [
                    '必须点到用户当下身体或情绪状态（来自上下文），再解释菜如何减轻负担或更舒服。',
                ],
            ],
            ReasonTextStyle::GoalOriented => [
                'label_cn' => '目标导向型',
                'tone' => '目标清晰、可执行，像营养室友给建议',
                'min_chars' => 110,
                'max_chars' => 220,
                'craft' => '显式呼应用户的饮食/健康目标或画像里的目标标签，再说明这道菜如何服务该目标（份量、烹饪方式、食材类型）。',
                'extra_must' => [
                    '必须出现用户目标或 diet_goal / health_goal / diet_preferences 中的具体词义（用自然语言转述即可，勿编造未给出的目标）。',
                ],
            ],
            ReasonTextStyle::SceneBased => [
                'label_cn' => '场景匹配型',
                'tone' => '懂你的生活节奏，仍保持理性推荐',
                'min_chars' => 100,
                'max_chars' => 200,
                'craft' => '强调时间成本、做饭频率、一人食/小家庭等场景，再解释这道菜为何更省事或更合拍。',
                'extra_must' => [
                    '必须点出至少一个生活场景线索（做饭频率、加班忙、外卖党、人数等），来自上下文或表单人数。',
                ],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function profileHasGoalSignals(array $profile): bool
    {
        $hg = $profile['health_goal'] ?? null;
        if (is_string($hg) && trim($hg) !== '') {
            return true;
        }
        $dg = $profile['diet_goal'] ?? [];
        if (is_array($dg) && $dg !== []) {
            return true;
        }
        $prefs = $profile['diet_preferences'] ?? [];
        if (! is_array($prefs)) {
            return false;
        }
        $blob = implode(' ', array_map(static fn (mixed $x): string => (string) $x, $prefs));
        foreach (['减脂', '增肌', '养胃', '清淡', '控糖', '低卡', '高蛋白', '少油', '少盐'] as $kw) {
            if (str_contains($blob, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $session
     * @param  list<string>  $systemTags
     * @param  array<string, mixed>  $date
     */
    private function sceneSignals(array $profile, array $session, array $systemTags, array $date): bool
    {
        $freq = (string) ($profile['cooking_frequency'] ?? '');
        if (in_array($freq, ['rarely', 'takeout'], true)) {
            return true;
        }

        $family = $profile['family_size'] ?? null;
        if (is_numeric($family) && (int) $family === 1) {
            return true;
        }

        $people = $session['people'] ?? null;
        if (is_numeric($people) && (int) $people === 1) {
            return true;
        }

        foreach ($profile['lifestyle_tags'] ?? [] as $t) {
            if (! is_string($t)) {
                continue;
            }
            foreach (['忙', '加班', '单人', '一人', '独居', '省事', '快手'] as $kw) {
                if (str_contains($t, $kw)) {
                    return true;
                }
            }
        }

        foreach ($systemTags as $tag) {
            if (! is_string($tag)) {
                continue;
            }
            foreach (['快手', '省时', '简单', '一锅', '外卖'] as $kw) {
                if (str_contains($tag, $kw)) {
                    return true;
                }
            }
        }

        if (empty($date['is_weekend']) && in_array($freq, ['sometimes', 'often'], true)) {
            foreach ($profile['lifestyle_tags'] ?? [] as $t) {
                if (is_string($t) && (str_contains($t, '忙') || str_contains($t, '加班'))) {
                    return true;
                }
            }
        }

        return false;
    }
}
