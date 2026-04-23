<?php

namespace App\Services;

use App\Models\RecommendationSession;
use App\Support\AiScene;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 在系统准备好的上下文与标签之上，调用大模型生成菜品、理由与食命文案（模型不得臆造天气/节日）。
 */
final class DishReasonGeneratorService
{
    public function __construct(
        private readonly AiModelCenterService $aiModelCenter,
        private readonly DestinyCopyStyleService $destinyCopyStyle,
        private readonly ReasonTextStyleService $reasonTextStyle,
        private readonly RecommendationTagSelectorService $tagSelector,
    ) {}

    /**
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array<string, mixed>  $sessionPreferences  normalized taste/avoid/people
     * @param  list<string>  $systemTags
     * @param  array{
     *   excluded_dishes: list<string>,
     *   pivot_key: string,
     *   pivot_label_cn: string,
     *   pivot_hint_cn: string
     * }|null  $reroll
     * @param  array<string, mixed>|null  $generatorHints  RecommendationOptimizationPipeline 注入的候选池与提示
     * @return array{
     *   recommended_dish: string,
     *   tags: list<string>,
     *   reason_text: string,
     *   destiny_text: string,
     *   alternatives: list<string>,
     *   title: string,
     *   cuisine: string|null,
     *   ingredients: list<string>,
     *   content: string,
     *   history_saved: bool,
     *   destiny_style: string,
     *   reason_style: string
     * }
     */
    public function generate(
        array $aggregatedContext,
        array $sessionPreferences,
        array $systemTags,
        ?array $reroll = null,
        ?array $generatorHints = null,
    ): array {
        $promptParts = $this->prepareCopyStylePromptParts($aggregatedContext, $systemTags);
        $ctxJson = $promptParts['ctx_json'];
        $system = $promptParts['system'];
        /** @var array<string, mixed> $copyControl */
        $copyControl = $promptParts['copy'];
        $tagsLine = implode('、', array_slice($systemTags, 0, 48));

        $prefTaste = (string) ($sessionPreferences['taste'] ?? '');
        $prefAvoid = (string) ($sessionPreferences['avoid'] ?? '');
        $prefPeople = $sessionPreferences['people'] ?? null;
        $peopleText = is_numeric($prefPeople) && (int) $prefPeople > 0 ? (string) (int) $prefPeople : '不限';

        $userLines = [
            '【系统上下文 JSON】',
            $ctxJson,
            '',
            '【系统规则标签】',
            $tagsLine !== '' ? $tagsLine : '（无）',
            '',
            '【本次表单偏好】',
            '- 口味 taste: '.($prefTaste !== '' ? $prefTaste : '无'),
            '- 忌口 avoid: '.($prefAvoid !== '' ? $prefAvoid : '无'),
            '- 人数 people: '.$peopleText,
        ];

        if ($reroll !== null) {
            /** @var list<string> $blocked */
            $blocked = array_slice($reroll['excluded_dishes'] ?? [], 0, 24);
            $blockedLine = $blocked !== [] ? implode('、', $blocked) : '（无）';
            $userLines[] = '';
            $userLines[] = '【换一道推荐（同一轮会话，上下文不变）】';
            $userLines[] = '- 以下主菜在本轮已推荐过：'.$blockedLine;
            $userLines[] = '- 新的 recommended_dish 严禁与上述任一菜名相同或仅换少量同义词（如「番茄炒蛋」↔「蕃茄鸡蛋」视为重复）。';
            $userLines[] = '- 本轮主菜定位：「'.($reroll['pivot_label_cn'] ?? '').'」——'.($reroll['pivot_hint_cn'] ?? '');
            $userLines[] = '- 味型/烹饪方式/主料类别应与上一轮推荐有明显差异，但仍须符合忌口、人数与上下文事实。';
            $userLines[] = '- reason_text、destiny_text、alternatives、tags 必须全部重写，不得复述上一轮的句子或结构。';
        }

        if ($generatorHints !== null) {
            $this->appendOptimizationHints($userLines, $generatorHints);
        }

        $userLines = array_merge($userLines, $promptParts['user_suffix']);
        $user = implode("\n", $userLines);

        /** @var list<string> $excludedForTemplate */
        $excludedForTemplate = $reroll !== null ? array_slice($reroll['excluded_dishes'] ?? [], 0, 48) : [];

        $runtime = $this->resolveRuntimeConfigSafely();
        $baseUrl = rtrim((string) ($runtime['base_url'] ?? ''), '/');
        $apiPath = ltrim((string) (($runtime['model']['api_path'] ?? '/chat/completions')), '/');
        $modelCode = (string) ($runtime['model']['model_code'] ?? '');
        $apiKey = (string) ($runtime['api_key'] ?? '');
        $aiReady = is_array($runtime) && $baseUrl !== '' && $modelCode !== '' && $apiKey !== '';

        if ($aiReady) {
            $timeoutSec = max(8, (int) ceil(((int) ($runtime['timeout_ms'] ?? 120000)) / 1000));
            $temperature = isset($runtime['temperature']) ? (float) $runtime['temperature'] : 0.65;
            if ($reroll !== null) {
                $temperature = min(0.92, $temperature + 0.08);
            }
            $requestUrl = $baseUrl.'/'.$apiPath;
            $payload = [
                'model' => $modelCode,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => $temperature,
            ];

            $parsed = $this->requestAndParseSafe(
                $requestUrl,
                $payload,
                $timeoutSec,
                $apiKey,
                $runtime['fallback_model_codes'] ?? []
            );
            $dish = is_array($parsed)
                ? Str::limit(trim((string) ($parsed['recommended_dish'] ?? '')), 80, '')
                : '';

            if ($dish !== '' && $reroll !== null) {
                /** @var list<string> $excluded */
                $excluded = $reroll['excluded_dishes'] ?? [];
                if (RecommendationSession::isExcluded($dish, $excluded)) {
                    $userRetry = $user."\n\n【纠错】你输出的主菜仍在禁止列表中。请换一种完全不同的菜，严格遵守禁止列表。";
                    $payload['messages'] = [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $userRetry],
                    ];
                    $payload['temperature'] = min(0.95, $temperature + 0.05);
                    $parsed = $this->requestAndParseSafe(
                        $requestUrl,
                        $payload,
                        $timeoutSec,
                        $apiKey,
                        $runtime['fallback_model_codes'] ?? []
                    );
                    $dish = is_array($parsed)
                        ? Str::limit(trim((string) ($parsed['recommended_dish'] ?? '')), 80, '')
                        : '';
                    if ($dish !== '' && RecommendationSession::isExcluded($dish, $excluded)) {
                        $parsed = null;
                        $dish = '';
                    }
                }
            }

            if (is_array($parsed) && $dish !== '') {
                $fromAi = $this->buildStructuredResultFromParsed(
                    $parsed,
                    $copyControl,
                    $aggregatedContext,
                    $sessionPreferences,
                    $dish,
                );
                if ($fromAi !== null) {
                    return $fromAi;
                }
            }
        }

        return $this->buildTemplateFallback(
            $aggregatedContext,
            $sessionPreferences,
            $copyControl,
            $generatorHints,
            $excludedForTemplate,
            null,
        );
    }

    /**
     * 用户点选备选菜名：在同样上下文下为该菜生成完整说明、新标签与新备选列表。
     *
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array<string, mixed>  $sessionPreferences
     * @param  list<string>  $systemTags
     * @param  list<string>  $excludedPastMains  本会话已作为主菜出现过的菜名
     * @param  array<string, mixed>|null  $generatorHints
     * @return array{
     *   recommended_dish: string,
     *   tags: list<string>,
     *   reason_text: string,
     *   destiny_text: string,
     *   alternatives: list<string>,
     *   title: string,
     *   cuisine: string|null,
     *   ingredients: list<string>,
     *   content: string,
     *   history_saved: bool,
     *   destiny_style: string,
     *   reason_style: string
     * }
     */
    public function generateSelectAlternative(
        array $aggregatedContext,
        array $sessionPreferences,
        array $systemTags,
        string $selectedDish,
        array $excludedPastMains,
        ?string $previousMain = null,
        ?array $generatorHints = null,
    ): array {
        $promptParts = $this->prepareCopyStylePromptParts($aggregatedContext, $systemTags);
        $ctxJson = $promptParts['ctx_json'];
        $system = $promptParts['system'];
        /** @var array<string, mixed> $copyControl */
        $copyControl = $promptParts['copy'];
        $tagsLine = implode('、', array_slice($systemTags, 0, 48));

        $prefTaste = (string) ($sessionPreferences['taste'] ?? '');
        $prefAvoid = (string) ($sessionPreferences['avoid'] ?? '');
        $prefPeople = $sessionPreferences['people'] ?? null;
        $peopleText = is_numeric($prefPeople) && (int) $prefPeople > 0 ? (string) (int) $prefPeople : '不限';

        $selectedTrim = Str::limit(trim($selectedDish), 80, '');
        $blocked = array_slice($excludedPastMains, 0, 24);
        $blockedLine = $blocked !== [] ? implode('、', $blocked) : '（无）';
        $prev = $previousMain !== null && trim($previousMain) !== '' ? trim($previousMain) : '（用户刚刚的主菜）';

        $userBaseLines = [
            '【系统上下文 JSON】',
            $ctxJson,
            '',
            '【系统规则标签】',
            $tagsLine !== '' ? $tagsLine : '（无）',
            '',
            '【本次表单偏好】',
            '- 口味 taste: '.($prefTaste !== '' ? $prefTaste : '无'),
            '- 忌口 avoid: '.($prefAvoid !== '' ? $prefAvoid : '无'),
            '- 人数 people: '.$peopleText,
            '',
            '【用户点选备选（同一轮会话，上下文不变）】',
            '- 用户从上一轮展示的备选列表中，主动点选「'.$selectedTrim.'」作为今日主菜。',
            '- recommended_dish 必须对应这道菜（用字可微调如常见别称，但不得换成完全不同的菜）。',
            '- 刚刚的上一条主菜大约是「'.$prev.'」；请从新的角度说明「今天为什么适合吃 '.$selectedTrim.'」，reason_text 与 destiny_text 须全部重写，不得套用上一轮句子。',
            '- 旧的 tags 全部作废，请生成新的 tags。',
            '- alternatives：给出 2～4 道与主菜「'.$selectedTrim.'」不同的菜名；且不得与下列本轮已主荐过的菜式重复：'.$blockedLine.'；也不得在 alternatives 中再次出现「'.$selectedTrim.'」。',
        ];
        if ($generatorHints !== null) {
            $this->appendOptimizationHints($userBaseLines, $generatorHints, true);
        }
        $userBaseLines = array_merge($userBaseLines, $promptParts['user_suffix']);
        $userBase = implode("\n", $userBaseLines);

        /** @var list<string> $excludedForTemplate */
        $excludedForTemplate = array_slice($excludedPastMains, 0, 48);

        $runtime = $this->resolveRuntimeConfigSafely();
        $baseUrl = rtrim((string) ($runtime['base_url'] ?? ''), '/');
        $apiPath = ltrim((string) (($runtime['model']['api_path'] ?? '/chat/completions')), '/');
        $modelCode = (string) ($runtime['model']['model_code'] ?? '');
        $apiKey = (string) ($runtime['api_key'] ?? '');
        $aiReady = is_array($runtime) && $baseUrl !== '' && $modelCode !== '' && $apiKey !== '';

        if ($aiReady && $selectedTrim !== '') {
            $timeoutSec = max(8, (int) ceil(((int) ($runtime['timeout_ms'] ?? 120000)) / 1000));
            $temperature = isset($runtime['temperature']) ? (float) $runtime['temperature'] : 0.65;
            $temperature = min(0.9, $temperature + 0.06);

            $requestUrl = $baseUrl.'/'.$apiPath;
            $payload = [
                'model' => $modelCode,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userBase],
                ],
                'temperature' => $temperature,
            ];

            $parsed = $this->requestAndParseSafe(
                $requestUrl,
                $payload,
                $timeoutSec,
                $apiKey,
                $runtime['fallback_model_codes'] ?? []
            );
            $dish = is_array($parsed)
                ? Str::limit(trim((string) ($parsed['recommended_dish'] ?? '')), 80, '')
                : '';

            if ($dish === '' || ! $this->selectedDishMatchesOutput($selectedTrim, $dish)) {
                $userRetry = $userBase."\n\n【纠错】recommended_dish 必须与用户点选的「{$selectedTrim}」为同一道菜，请修正后重新输出完整 JSON。";
                $payload['messages'] = [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userRetry],
                ];
                $payload['temperature'] = min(0.92, $temperature + 0.05);
                $parsed = $this->requestAndParseSafe(
                    $requestUrl,
                    $payload,
                    $timeoutSec,
                    $apiKey,
                    $runtime['fallback_model_codes'] ?? []
                );
                $dish = is_array($parsed)
                    ? Str::limit(trim((string) ($parsed['recommended_dish'] ?? '')), 80, '')
                    : '';
            }

            if (is_array($parsed) && $dish !== '' && $this->selectedDishMatchesOutput($selectedTrim, $dish)) {
                $fromAi = $this->buildStructuredResultFromParsed(
                    $parsed,
                    $copyControl,
                    $aggregatedContext,
                    $sessionPreferences,
                    $dish,
                );
                if ($fromAi !== null) {
                    $alts = $this->normalizeStringList($parsed['alternatives'] ?? [], 6);
                    $alts = $this->filterAlternativesNotMainNorBlocked($alts, $dish, $blocked);
                    if ($alts === []) {
                        $alts = $this->templateAlternativesFromCatalog($dish, $excludedForTemplate, $generatorHints);
                    }
                    $fromAi['alternatives'] = $alts;

                    return $fromAi;
                }
            }
        }

        return $this->buildTemplateFallback(
            $aggregatedContext,
            $sessionPreferences,
            $copyControl,
            $generatorHints,
            $excludedForTemplate,
            $selectedTrim !== '' ? $selectedTrim : null,
        );
    }

    /**
     * @param  array<string, mixed>  $aggregatedContext
     * @param  list<string>  $systemTags
     * @return array{ctx_json: string, system: string, user_suffix: list<string>, copy: array<string, mixed>}
     */
    private function prepareCopyStylePromptParts(array $aggregatedContext, array $systemTags): array
    {
        $destiny = $this->destinyCopyStyle->build($aggregatedContext, $systemTags);
        $reason = $this->reasonTextStyle->build($aggregatedContext, $systemTags);

        $ctxForAi = $aggregatedContext;
        $ctxForAi['reason_copy_control'] = $reason['for_context'];
        $ctxForAi['destiny_copy_control'] = $destiny['for_context'];

        $ctxJson = json_encode(
            $ctxForAi,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        ) ?: '{}';

        $rMin = (int) $reason['reason_min_chars'];
        $rMax = (int) $reason['reason_max_chars'];
        $dMin = (int) $destiny['destiny_min_chars'];
        $dMax = (int) $destiny['destiny_max_chars'];
        $reasonStyle = (string) $reason['style'];
        $destinyStyle = (string) $destiny['style'];

        $system = implode("\n", array_merge(
            [
                '你是「饭否」小程序的今日晚餐推荐官。',
                '你将收到系统已校验的结构化 JSON 上下文（含日期、天气摘要、节日/生日标记、用户画像、历史菜名摘要、当日状态）以及系统规则生成的标签列表。',
                'JSON 内 reason_copy_control、destiny_copy_control 分别为推荐理由与食命文案的系统选定参数（风格、语气、长度），须严格遵守。',
                '严禁编造上下文中不存在的事实（例如未提供雨天却写「今天下雨」）。',
                '请只输出一个 JSON 对象，不要 Markdown、不要代码围栏、不要任何多余说明。',
                '【职责划分】',
                '- reason_text：理性解释层，回答「为什么今天推荐这道菜」，以因果与可核对线索为主，不要签文体或押韵金句。',
                '- destiny_text：情绪价值层，一条短句，负责轻松陪伴感；不得承担长篇解释。',
                '- 二者禁止互换职责，禁止复用同一比喻、同一句式或互相复述。',
                '【表达约束】',
                '- 禁止复述系统输入参数原文：不要出现字段名/键名，不要逐条复读日期、温度、分值、标签名等原始参数。',
                '- 可以表达为自然结论，但不要写成「今天是周X、XX℃、你胃口XX」这类参数播报体。',
                '- 语气要像真实推荐文案，避免「根据参数/上下文/标签」等技术口吻。',
                'JSON 字段要求：',
                '- recommended_dish: string，主推荐菜名',
                '- tags: string[]，可填若干短词自检用；用户看到的推荐标签由服务端按规则统一生成，勿把 destiny_text/reason_text 拆成标签，勿写冗长句。',
                '- reason_text: string，约 '.$rMin.'～'.$rMax.' 字（含标点），一段连续文本；须遵循下方【推荐理由】的结构、风格与禁止项（系统 reason_style: '.$reasonStyle.'）。',
                '- destiny_text: string，单行短句、不换行；约 '.$dMin.'～'.$dMax.' 个汉字（含标点）；须遵循下方【食命文案】（系统 destiny_style: '.$destinyStyle.'），与 reason_text 不得雷同。',
                '- alternatives: string[]，2～4 个备选菜名',
                '- cuisine: string，菜系',
                '- ingredients: string[]，5～10 种主要食材',
                '- recipe_content: string，简洁可执行的做法步骤，与 ingredients 一致',
                '',
            ],
            $reason['system_lines'],
            [''],
            $destiny['system_lines'],
        ));

        $userSuffix = array_merge($reason['user_lines'], $destiny['user_lines']);

        $mergedCopy = [
            'destiny_style' => $destinyStyle,
            'destiny_min_chars' => $dMin,
            'destiny_max_chars' => $dMax,
            'reason_style' => $reasonStyle,
            'reason_min_chars' => $rMin,
            'reason_max_chars' => $rMax,
        ];

        return [
            'ctx_json' => $ctxJson,
            'system' => $system,
            'user_suffix' => $userSuffix,
            'copy' => $mergedCopy,
        ];
    }

    private function finalizeReasonText(string $raw, int $maxChars): string
    {
        $collapsed = str_replace(["\r\n", "\r", "\n"], ' ', $raw);
        $singleSpaced = trim((string) preg_replace('/\s+/u', ' ', $collapsed));
        if ($maxChars > 0 && mb_strlen($singleSpaced) > $maxChars + 24) {
            return rtrim(mb_substr($singleSpaced, 0, $maxChars)).'…';
        }

        return $singleSpaced;
    }

    private function deparameterizeReasonText(string $reason): string
    {
        if ($reason === '') {
            return $reason;
        }

        $normalized = $reason;
        $normalized = (string) preg_replace('/^今天是[^，。；]{0,24}[，。；]\s*/u', '', $normalized);
        $normalized = (string) preg_replace('/(?:阴|晴|多云|小雨|中雨|大雨|阵雨|雷阵雨)?\s*\d{1,2}\s*℃/u', '这样的天气', $normalized);
        $normalized = str_replace(['根据你本次参数', '根据参数', '根据上下文标签', '根据系统标签'], '结合你今天的状态', $normalized);
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $normalized));

        return $normalized !== '' ? $normalized : $reason;
    }

    private function finalizeDestinyText(string $raw, int $maxChars): string
    {
        $collapsed = str_replace(["\r\n", "\r", "\n"], ' ', $raw);
        $singleSpaced = trim((string) preg_replace('/\s+/u', ' ', $collapsed));
        if ($maxChars > 0 && mb_strlen($singleSpaced) > $maxChars) {
            return mb_substr($singleSpaced, 0, $maxChars);
        }

        return $singleSpaced;
    }

    /**
     * @param  list<string>  $lines
     */
    private function appendOptimizationHints(array &$lines, array $hints, bool $alternativeLockedMain = false): void
    {
        $primary = $hints['primary_candidates'] ?? [];
        $pool = $hints['alternative_pool'] ?? [];
        if (! is_array($primary)) {
            $primary = [];
        }
        if (! is_array($pool)) {
            $pool = [];
        }
        $primary = array_values(array_filter(array_map('strval', array_slice($primary, 0, 8))));
        $pool = array_values(array_filter(array_map('strval', array_slice($pool, 0, 24))));

        if ($primary === [] && $pool === []) {
            return;
        }

        $lines[] = '';
        $lines[] = '【系统优化候选池 — 规则引擎打分/去重后，请务必遵守】';
        if ($primary !== []) {
            $lines[] = '- 主菜 recommended_dish 必须是下列菜名之一（允许常见别称，但须明确是同一道菜）：'.implode('、', $primary).'。';
        }
        if ($pool !== []) {
            $lines[] = '- alternatives 中的菜名应优先从下列池中挑选（允许微调用字）：'.implode('、', $pool).'。';
        }
        if (! empty($hints['explain_style_note']) && is_string($hints['explain_style_note'])) {
            $lines[] = '- '.$hints['explain_style_note'];
        }
        $ch = (string) ($hints['complexity_hint'] ?? '');
        if ($ch === 'prefer_quick') {
            $lines[] = '- 用户近期反馈偏「简单快手」：尽量选择步骤少、耗时短的方案（可用池子里的快手菜）。';
        }
        if ($alternativeLockedMain) {
            $lines[] = '- 本条为备选切换：主菜已锁定为用户所选，alternatives 严禁再包含该主菜或与会话禁止列表冲突的菜。';
        }
    }

    private function selectedDishMatchesOutput(string $selected, string $output): bool
    {
        if ($selected === '' || $output === '') {
            return false;
        }
        if (RecommendationSession::dishKey($selected) === RecommendationSession::dishKey($output)) {
            return true;
        }
        if (mb_strpos($output, $selected) !== false || mb_strpos($selected, $output) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>  $alts
     * @param  list<string>  $blocked
     * @return list<string>
     */
    private function filterAlternativesNotMainNorBlocked(array $alts, string $mainDish, array $blocked): array
    {
        $mainK = RecommendationSession::dishKey($mainDish);
        $out = [];
        foreach ($alts as $a) {
            $k = RecommendationSession::dishKey($a);
            if ($k === $mainK) {
                continue;
            }
            if (RecommendationSession::isExcluded($a, $blocked)) {
                continue;
            }
            $out[] = $a;
            if (count($out) >= 4) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<string, mixed>  $copyControl
     * @return array<string, mixed>|null
     */
    private function buildStructuredResultFromParsed(
        array $parsed,
        array $copyControl,
        array $aggregatedContext,
        array $sessionPreferences,
        string $dish,
    ): ?array {
        $alts = $this->normalizeStringList($parsed['alternatives'] ?? [], 6);
        $reason = $this->finalizeReasonText(
            (string) ($parsed['reason_text'] ?? ''),
            (int) ($copyControl['reason_max_chars'] ?? 200)
        );
        $reason = $this->deparameterizeReasonText($reason);
        $destiny = $this->finalizeDestinyText(
            (string) ($parsed['destiny_text'] ?? ''),
            (int) ($copyControl['destiny_max_chars'] ?? 32)
        );
        $cuisine = trim((string) ($parsed['cuisine'] ?? ''));
        $ingredients = $this->normalizeStringList($parsed['ingredients'] ?? [], 14);
        $recipe = trim((string) ($parsed['recipe_content'] ?? ''));

        if ($reason === '' || $destiny === '' || $recipe === '' || $ingredients === []) {
            return null;
        }

        $displayTags = $this->tagSelector->selectDisplayTags($aggregatedContext, $dish, $sessionPreferences);

        return [
            'recommended_dish' => $dish,
            'tags' => $displayTags,
            'reason_text' => $reason,
            'destiny_text' => $destiny,
            'alternatives' => $alts !== [] ? $alts : array_slice(['清蒸鲈鱼', '番茄鸡蛋面', '冬瓜排骨汤'], 0, 3),
            'title' => $dish,
            'cuisine' => $cuisine !== '' ? $cuisine : null,
            'ingredients' => $ingredients,
            'content' => $recipe,
            'history_saved' => false,
            'destiny_style' => (string) ($copyControl['destiny_style'] ?? ''),
            'reason_style' => (string) ($copyControl['reason_style'] ?? ''),
        ];
    }

    /**
     * @param  list<string>  $excludedForMain
     * @return array<string, mixed>
     */
    private function buildTemplateFallback(
        array $aggregatedContext,
        array $sessionPreferences,
        array $copyControl,
        ?array $generatorHints,
        array $excludedForMain,
        ?string $lockedMainDish,
    ): array {
        $pick = $this->pickTemplateDishAndRow($generatorHints, $excludedForMain, $lockedMainDish);
        $dish = $pick['name'];
        $row = $pick['row'];

        $ingredients = [];
        if ($row !== null && isset($row['ingredients']) && is_array($row['ingredients'])) {
            $ingredients = $this->normalizeStringList($row['ingredients'], 14);
        }
        if ($ingredients === []) {
            $ingredients = ['主料适量', '姜蒜', '盐', '食用油', '酱油'];
        }

        $cuisine = null;
        if ($row !== null && isset($row['cuisine_type'])) {
            $cuisine = $this->mapCuisineTypeToLabel((string) $row['cuisine_type']);
        }

        $ingLine = implode('、', array_slice($ingredients, 0, 8));
        $reason = $this->finalizeReasonText(
            '结合你本次填写的小偏好与今晚的节奏，「'.$dish.'」更贴近「立刻能做、吃完不撑」的落地感；用料常见，调味可以按口味微调。',
            (int) ($copyControl['reason_max_chars'] ?? 200)
        );
        $destiny = $this->finalizeDestinyText(
            '今晚就吃这一口，刚刚好。',
            (int) ($copyControl['destiny_max_chars'] ?? 32)
        );
        $recipe = "1）准备：{$ingLine}。\n2）热锅少油，依次处理主料与配菜，注意分次调味、避免过咸。\n3）出锅前试味，必要时加一点点糖或醋平衡，装盘趁热吃。";

        $alternatives = $this->templateAlternativesFromCatalog($dish, $excludedForMain, $generatorHints);
        $displayTags = $this->tagSelector->selectDisplayTags($aggregatedContext, $dish, $sessionPreferences);

        return [
            'recommended_dish' => $dish,
            'tags' => $displayTags,
            'reason_text' => $reason,
            'destiny_text' => $destiny,
            'alternatives' => $alternatives,
            'title' => $dish,
            'cuisine' => $cuisine,
            'ingredients' => $ingredients,
            'content' => $recipe,
            'history_saved' => false,
            'destiny_style' => (string) ($copyControl['destiny_style'] ?? ''),
            'reason_style' => (string) ($copyControl['reason_style'] ?? ''),
            'recommendation_fallback' => true,
        ];
    }

    /**
     * @param  list<string>  $excluded
     * @return array{name: string, row: array<string, mixed>|null}
     */
    private function pickTemplateDishAndRow(?array $generatorHints, array $excluded, ?string $lockedMain): array
    {
        if ($lockedMain !== null && trim($lockedMain) !== '') {
            $n = Str::limit(trim($lockedMain), 80, '');

            return ['name' => $n, 'row' => $this->findCatalogRowByDishName($n)];
        }

        $primary = [];
        if ($generatorHints !== null && isset($generatorHints['primary_candidates']) && is_array($generatorHints['primary_candidates'])) {
            $primary = array_values(array_filter(array_map('strval', $generatorHints['primary_candidates'])));
        }
        foreach ($primary as $cand) {
            $cand = Str::limit(trim($cand), 80, '');
            if ($cand === '' || RecommendationSession::isExcluded($cand, $excluded)) {
                continue;
            }

            return ['name' => $cand, 'row' => $this->findCatalogRowByDishName($cand)];
        }

        foreach ($this->catalogRows() as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '' || RecommendationSession::isExcluded($name, $excluded)) {
                continue;
            }

            return ['name' => Str::limit($name, 80, ''), 'row' => $row];
        }

        return ['name' => '番茄鸡蛋面', 'row' => $this->findCatalogRowByDishName('番茄鸡蛋面')];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogRows(): array
    {
        $c = config('recommendation_dish_catalog', []);

        return is_array($c) ? $c : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findCatalogRowByDishName(string $name): ?array
    {
        $k = RecommendationSession::dishKey($name);
        if ($k === '') {
            return null;
        }
        foreach ($this->catalogRows() as $row) {
            if (! is_array($row)) {
                continue;
            }
            $n = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($n === '') {
                continue;
            }
            if (RecommendationSession::dishKey($n) === $k) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $excluded
     * @return list<string>
     */
    private function templateAlternativesFromCatalog(string $mainDish, array $excluded, ?array $generatorHints): array
    {
        $pool = [];
        if ($generatorHints !== null && isset($generatorHints['alternative_pool']) && is_array($generatorHints['alternative_pool'])) {
            $pool = array_values(array_filter(array_map('strval', $generatorHints['alternative_pool'])));
        }
        $out = [];
        $mainK = RecommendationSession::dishKey($mainDish);
        foreach ($pool as $name) {
            $name = Str::limit(trim($name), 64, '');
            if ($name === '') {
                continue;
            }
            if (RecommendationSession::dishKey($name) === $mainK) {
                continue;
            }
            if (RecommendationSession::isExcluded($name, $excluded)) {
                continue;
            }
            $out[] = $name;
            if (count($out) >= 3) {
                return $out;
            }
        }
        foreach ($this->catalogRows() as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = isset($row['name']) ? trim((string) $row['name']) : '';
            if ($name === '' || RecommendationSession::dishKey($name) === $mainK) {
                continue;
            }
            if (RecommendationSession::isExcluded($name, $excluded)) {
                continue;
            }
            $out[] = Str::limit($name, 64, '');
            if (count($out) >= 3) {
                break;
            }
        }

        return $out !== [] ? $out : array_slice(['清蒸鲈鱼', '番茄鸡蛋面', '冬瓜排骨汤'], 0, 3);
    }

    private function mapCuisineTypeToLabel(string $code): ?string
    {
        $map = [
            'home' => '家常菜',
            'cantonese' => '粤菜',
            'sichuan' => '川菜',
            'shanghai' => '本帮菜',
            'shandong' => '鲁菜',
            'jiangsu' => '淮扬菜',
            'hunan' => '湘菜',
            'beijing' => '京菜',
            'northern' => '北方菜',
            'taiwan' => '台湾菜',
            'japanese' => '日式',
            'western' => '西式',
            'thai' => '泰式',
            'fusion' => '融合菜',
        ];

        return $map[$code] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestAndParseSafe(
        string $requestUrl,
        array $payload,
        int $timeoutSec,
        string $apiKey,
        mixed $fallbackModelCodes = []
    ): ?array
    {
        if ($requestUrl === '' || $apiKey === '') {
            return null;
        }
        try {
            $modelRaw = (string) ($payload['model'] ?? '');
            $cfgFallbacks = [];
            if (is_array($fallbackModelCodes)) {
                foreach ($fallbackModelCodes as $item) {
                    if (! is_string($item)) {
                        continue;
                    }
                    $code = trim($item);
                    if ($code !== '') {
                        $cfgFallbacks[] = $code;
                    }
                }
            }
            if ($cfgFallbacks === []) {
                $cfgFallbacks = ['gpt-5-mini', 'gpt-4o-mini'];
            }

            $modelCandidates = array_values(array_unique(array_filter([
                $modelRaw,
                ...$cfgFallbacks,
            ], fn ($m): bool => is_string($m) && trim($m) !== '')));

            foreach ($modelCandidates as $candidateModel) {
                $candidatePayload = $payload;
                $candidatePayload['model'] = $candidateModel;

                $isResponsesApi = str_ends_with(strtolower($requestUrl), '/responses');
                if ($isResponsesApi) {
                    $messages = is_array($candidatePayload['messages'] ?? null) ? $candidatePayload['messages'] : [];
                    $input = [];
                    foreach ($messages as $msg) {
                        $role = is_array($msg) ? (string) ($msg['role'] ?? 'user') : 'user';
                        $content = is_array($msg) ? ($msg['content'] ?? '') : '';
                        $text = is_string($content) ? $content : '';
                        $input[] = [
                            'role' => $role !== '' ? $role : 'user',
                            'content' => [
                                ['type' => 'input_text', 'text' => $text],
                            ],
                        ];
                    }
                    $candidatePayload = [
                        'model' => $candidateModel,
                        'temperature' => $candidatePayload['temperature'] ?? null,
                        'input' => $input,
                    ];
                }

                $resp = Http::timeout($timeoutSec)
                    ->acceptJson()
                    ->withToken($apiKey)
                    ->post($requestUrl, $candidatePayload);

                if (! $resp->successful()) {
                    continue;
                }

                $data = $resp->json();
                $content = null;
                $chatContent = $data['choices'][0]['message']['content'] ?? null;
                if (is_string($chatContent) && trim($chatContent) !== '') {
                    $content = $chatContent;
                } elseif (is_string($data['output_text'] ?? null) && trim((string) $data['output_text']) !== '') {
                    $content = (string) $data['output_text'];
                } elseif (isset($data['output']) && is_array($data['output'])) {
                    $segments = [];
                    foreach ($data['output'] as $item) {
                        if (! is_array($item) || ! isset($item['content']) || ! is_array($item['content'])) {
                            continue;
                        }
                        foreach ($item['content'] as $part) {
                            if (is_array($part) && isset($part['text']) && is_string($part['text']) && trim($part['text']) !== '') {
                                $segments[] = $part['text'];
                            }
                        }
                    }
                    if ($segments !== []) {
                        $content = implode("\n", $segments);
                    }
                }

                $parsed = $this->tryParseJsonFromText($content);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function tryParseJsonFromText(?string $text): ?array
    {
        if ($text === null || $text === '') {
            return null;
        }
        $s = trim($text);
        if ($s !== '' && ($s[0] === '{' || $s[0] === '[')) {
            try {
                $v = json_decode($s, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($v)) {
                    return $v;
                }
            } catch (\Throwable) {
                /* fall through */
            }
        }
        $start = strpos($s, '{');
        $end = strrpos($s, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($s, $start, $end - $start + 1);
            try {
                $v = json_decode($slice, true, 512, JSON_THROW_ON_ERROR);

                return is_array($v) ? $v : null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * AI 配置缺失/禁用时不抛给客户端，改为走模板兜底。
     *
     * @return array<string,mixed>|null
     */
    private function resolveRuntimeConfigSafely(): ?array
    {
        try {
            return $this->aiModelCenter->resolveRuntimeConfig(AiScene::RecipeTextGeneration->value);
        } catch (HttpResponseException $e) {
            $message = (string) $e->getMessage();
            if (str_contains($message, 'scene_config_not_found') || str_contains($message, 'scene_config_disabled')) {
                Log::warning('recipe_text_generation runtime config unavailable, fallback to template.', [
                    'message' => $message,
                ]);
                return null;
            }

            throw $e;
        }
    }

    /**
     * @return list<string>
     */
    private function normalizeStringList(mixed $raw, int $max): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            $s = is_string($item) ? trim($item) : '';
            if ($s !== '') {
                $out[] = Str::limit($s, 64, '');
            }
            if (count($out) >= $max) {
                break;
            }
        }

        return $out;
    }
}
