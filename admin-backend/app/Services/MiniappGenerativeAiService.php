<?php

namespace App\Services;

use App\Models\AiModelConfig;
use App\Models\FeatureDataRecord;
use App\Support\AiScene;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 小程序生成式 AI（Laravel 直连），走模型中心场景配置。
 */
final class MiniappGenerativeAiService
{
    /**
     * @return array<string, mixed>
     */
    public function fortuneCooking(int $userId, array $body): array
    {
        $fortuneType = in_array((string) ($body['fortune_type'] ?? $body['fortuneType'] ?? 'daily'), ['daily', 'mood', 'couple', 'number'], true)
            ? (string) ($body['fortune_type'] ?? $body['fortuneType'])
            : 'daily';
        $locale = (string) ($body['locale'] ?? 'zh-CN');
        $payload = [
            'fortuneType' => $fortuneType,
            'daily' => $body['daily'] ?? null,
            'mood' => $body['mood'] ?? null,
            'couple' => $body['couple'] ?? null,
            'number' => $body['number'] ?? null,
            'locale' => $locale,
        ];

        $sys = implode("\n", [
            '你是“饭否”小程序的玄学占卜生成器。',
            '请只输出 JSON，不要输出任何额外解释或 Markdown。',
            'JSON 必须包含字段：',
            '- type: string（只能为 daily|mood|couple|number）',
            '- date: string（YYYY-MM-DD）',
            '- dishName: string（菜名/占卜题目，用中文）',
            '- reason: string（简短理由）',
            '- luckyIndex: number（1-10，整数）',
            '- description: string（神秘解析，较详细）',
            '- tips: string[]（建议，3-5 条）',
            '- difficulty: string（easy|medium|hard）',
            '- cookingTime: number（分钟，15-90）',
            '- mysticalMessage: string（占卜师的话）',
            '- ingredients: string[]（神秘食材，建议 5-12 个）',
            '- steps: string[]（制作步骤/指引，建议 3-6 条，字符串即可）',
        ]);

        $userPrompt = $this->buildFortuneUserPrompt($fortuneType, $body, $locale);

        try {
            $jsonObj = $this->chatJsonObject(AiScene::RecipeTextGeneration->value, $sys, $userPrompt, 0.7);
            $allowed = ['daily', 'mood', 'couple', 'number'];
            $outType = in_array((string) ($jsonObj['type'] ?? ''), $allowed, true) ? (string) $jsonObj['type'] : $fortuneType;
            $date = trim((string) ($jsonObj['date'] ?? now()->toDateString()));
            $dishName = trim((string) ($jsonObj['dishName'] ?? $jsonObj['dish_name'] ?? ''));
            $luckyIndex = (int) round((float) ($jsonObj['luckyIndex'] ?? $jsonObj['lucky_index'] ?? 7));
            $luckyIndex = max(1, min(10, $luckyIndex));
            $cookingTime = (int) round((float) ($jsonObj['cookingTime'] ?? $jsonObj['cooking_time'] ?? 30));
            $cookingTime = max(15, min(90, $cookingTime));

            $result = [
                'id' => trim((string) ($jsonObj['id'] ?? '')) !== '' ? (string) $jsonObj['id'] : 'fortune-'.time(),
                'type' => $outType,
                'date' => $date !== '' ? $date : now()->toDateString(),
                'dishName' => $dishName !== '' ? $dishName : '神秘料理',
                'reason' => (string) ($jsonObj['reason'] ?? ''),
                'luckyIndex' => $luckyIndex,
                'description' => (string) ($jsonObj['description'] ?? ''),
                'tips' => $this->normalizeStringList($jsonObj['tips'] ?? [], 6),
                'difficulty' => $this->normalizeDifficulty((string) ($jsonObj['difficulty'] ?? 'medium')),
                'cookingTime' => $cookingTime,
                'mysticalMessage' => (string) ($jsonObj['mysticalMessage'] ?? $jsonObj['mystical_message'] ?? ''),
                'ingredients' => $this->normalizeIngredients($jsonObj['ingredients'] ?? []),
                'steps' => $this->normalizeFortuneSteps($jsonObj['steps'] ?? []),
            ];

            $this->logFeature('fortune_cooking', $fortuneType, 'success', $userId, $payload, $result['dishName'], Str::limit($result['description'], 200));

            return ['result' => $result, 'history_saved' => false];
        } catch (\Throwable $e) {
            $this->logFeature('fortune_cooking', $fortuneType, 'failed', $userId, $payload, null, null, Str::limit($e->getMessage(), 500));
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sauceRecommend(int $userId, array $body): array
    {
        $preferences = is_array($body['preferences'] ?? null) ? $body['preferences'] : (is_array($body['pref'] ?? null) ? $body['pref'] : []);
        $locale = (string) ($body['locale'] ?? 'zh-CN');
        $input = ['preferences' => $preferences, 'locale' => $locale];

        $spice = $this->clampInt($preferences['spiceLevel'] ?? null, 1, 5, 3);
        $sweet = $this->clampInt($preferences['sweetLevel'] ?? null, 1, 5, 3);
        $salt = $this->clampInt($preferences['saltLevel'] ?? null, 1, 5, 3);
        $sour = $this->clampInt($preferences['sourLevel'] ?? null, 1, 5, 3);
        $useCase = is_array($preferences['useCase'] ?? null) ? array_map('strval', $preferences['useCase']) : [];
        $ing = is_array($preferences['availableIngredients'] ?? null) ? array_map('strval', $preferences['availableIngredients']) : [];

        $sys = implode("\n", [
            '你是“饭否”小程序的酱料推荐生成器（sauce-recommend）。',
            '请只输出 JSON，不要输出任何额外解释或 Markdown。',
            'JSON 必须包含字段：',
            '- recommendations: string[]（推荐酱名，至少 3 个，建议 5 个）',
        ]);

        $user = implode("\n", [
            '请根据用户口味与场景，推荐适合搭配的酱料名称。',
            "口味辣度 spiceLevel={$spice}/5",
            "甜度 sweetLevel={$sweet}/5",
            "咸度 saltLevel={$salt}/5",
            "酸度 sourLevel={$sour}/5",
            '使用场景 useCase: '.($useCase !== [] ? implode('、', $useCase) : '无'),
            '现有食材 availableIngredients: '.($ing !== [] ? implode('、', $ing) : '无'),
            "语言 locale={$locale}",
            '',
            '要求：酱名必须是中文、简洁可读（不要带“配方/步骤”）。',
        ]);

        try {
            $jsonObj = $this->chatJsonObject(AiScene::RecipeTextGeneration->value, $sys, $user, 0.7);
            $recs = $this->normalizeStringList($jsonObj['recommendations'] ?? [], 10);
            $title = $recs !== [] ? '酱料推荐（'.count($recs).'个）' : '酱料推荐';
            $summary = Str::limit(implode('、', array_slice($recs, 0, 8)), 200);
            $this->logFeature('sauce_design', 'recommend', 'success', $userId, $input, $title, $summary);

            return ['recommendations' => $recs];
        } catch (\Throwable $e) {
            $this->logFeature('sauce_design', 'recommend', 'failed', $userId, $input, null, null, Str::limit($e->getMessage(), 500));
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sauceRecipe(int $userId, array $body): array
    {
        $name = trim((string) ($body['sauce_name'] ?? $body['sauceName'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('sauce_name_missing');
        }
        $locale = (string) ($body['locale'] ?? 'zh-CN');
        $input = ['sauce_name' => $name, 'locale' => $locale];

        $sys = implode("\n", [
            '你是“饭否”小程序的酱料配方生成器（sauce-recipe）。',
            '请只输出 JSON，不要输出任何额外解释或 Markdown。',
            'JSON 必须包含字段：',
            '- name: string（酱名）',
            '- category: string（只能为 spicy|garlic|sweet|complex|regional|fusion 其中之一）',
            '- ingredients: string[]（食材）',
            '- steps: string[]（制作步骤，建议 4-8 条，每条可直接展示）',
            '- makingTime: number（分钟）',
            '- difficulty: string（easy|medium|hard）',
            '- tips: string[]（建议 3-5 条）',
            '- storage: { method: string, duration: string, temperature: string }',
            '- pairings: string[]（搭配建议）',
            '- tags: string[]（标签）',
            '- description: string（酱料特色）',
        ]);

        $user = implode("\n", [
            "请生成酱料配方：{$name}",
            "语言 locale={$locale}",
            '',
            '要求：steps 需要能直接用于小程序展示；ingredients 与 steps 要一致；storage 三个字段用简短中文。',
        ]);

        try {
            $jsonObj = $this->chatJsonObject(AiScene::RecipeTextGeneration->value, $sys, $user, 0.7);
            $storage = is_array($jsonObj['storage'] ?? null) ? $jsonObj['storage'] : [];
            $recipe = [
                'id' => trim((string) ($jsonObj['id'] ?? '')) !== '' ? (string) $jsonObj['id'] : 'sauce-'.time(),
                'name' => trim((string) ($jsonObj['name'] ?? $name)) ?: $name,
                'category' => $this->normalizeSauceCategory((string) ($jsonObj['category'] ?? 'complex')),
                'ingredients' => $this->normalizeIngredients($jsonObj['ingredients'] ?? []),
                'steps' => $this->normalizeStringList($jsonObj['steps'] ?? [], 24),
                'makingTime' => $this->clampInt($jsonObj['makingTime'] ?? $jsonObj['making_time'] ?? null, 1, 240, 25),
                'difficulty' => $this->normalizeDifficulty((string) ($jsonObj['difficulty'] ?? 'medium')),
                'tips' => $this->normalizeStringList($jsonObj['tips'] ?? [], 8),
                'storage' => [
                    'method' => trim((string) ($storage['method'] ?? '密封保存')) ?: '密封保存',
                    'duration' => trim((string) ($storage['duration'] ?? '—')) ?: '—',
                    'temperature' => trim((string) ($storage['temperature'] ?? '—')) ?: '—',
                ],
                'pairings' => $this->normalizeStringList($jsonObj['pairings'] ?? [], 8),
                'tags' => $this->normalizeStringList($jsonObj['tags'] ?? [], 8),
                'description' => isset($jsonObj['description']) && is_string($jsonObj['description']) ? $jsonObj['description'] : null,
            ];
            $summary = Str::limit((string) ($recipe['description'] ?? implode('、', array_slice($recipe['ingredients'], 0, 10))), 200);
            $this->logFeature('sauce_design', 'recipe', 'success', $userId, $input, $recipe['name'], $summary);

            return ['recipe' => $recipe, 'history_saved' => false];
        } catch (\Throwable $e) {
            $this->logFeature('sauce_design', 'recipe', 'failed', $userId, $input, $name, null, Str::limit($e->getMessage(), 500));
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function tableMenu(int $userId, array $body): array
    {
        $config = is_array($body['config'] ?? null) ? $body['config'] : [];
        $locale = (string) ($body['locale'] ?? 'zh-CN');
        $input = ['config' => $config, 'locale' => $locale];

        $dishCount = isset($config['dishCount']) ? (float) $config['dishCount'] : 4.0;
        $flexible = (bool) ($config['flexibleCount'] ?? false);
        $count = $flexible
            ? max(3, min(8, (int) round($dishCount)))
            : max(2, min(10, (int) round($dishCount)));

        $tastes = is_array($config['tastes'] ?? null) ? array_map('strval', $config['tastes']) : [];
        $cuisineStyle = trim((string) ($config['cuisineStyle'] ?? ''));
        $diningScene = trim((string) ($config['diningScene'] ?? ''));
        $nutritionFocus = trim((string) ($config['nutritionFocus'] ?? ''));
        $customRequirement = trim((string) ($config['customRequirement'] ?? ''));
        $customDishes = is_array($config['customDishes'] ?? null) ? array_map('strval', $config['customDishes']) : [];

        $sys = str_replace(
            '{{count}}',
            (string) $count,
            implode("\n", [
                '你是“饭否”小程序的一桌菜策划器（table-menu）。',
                '请只输出 JSON，不要输出任何额外解释或 Markdown。',
                'JSON 必须包含字段：',
                '- dishes: Array，其中每个元素都包含：',
                '  - name: string（菜名）',
                '  - description: string（简短说明）',
                '  - category: string（菜系/类别）',
                '  - tags: string[]（最多 3-5 个标签，可为空数组）',
                '并确保 dishes 数量约为 {{count}}。',
            ])
        );

        $user = implode("\n", [
            '请生成家常好菜（总计约 count 道）。',
            '',
            '口味偏好 tastes: '.($tastes !== [] ? implode('、', $tastes) : '无'),
            "菜系/风格 cuisineStyle: {$cuisineStyle}",
            "用餐场景 diningScene: {$diningScene}",
            "营养侧重点 nutritionFocus: {$nutritionFocus}",
            "自定义要求 customRequirement: {$customRequirement}",
            '参考菜 customDishes: '.($customDishes !== [] ? implode('、', $customDishes) : '无'),
            '',
            "语言 locale: {$locale}",
            '',
            '注意：tags 必须是数组，description 要简短但具体。',
        ]);

        try {
            $jsonObj = $this->chatJsonObject(AiScene::RecipeTextGeneration->value, $sys, $user, 0.7);
            $dishesRaw = is_array($jsonObj['dishes'] ?? null) ? $jsonObj['dishes'] : [];
            $dishes = [];
            foreach (array_slice($dishesRaw, 0, 10) as $d) {
                if (! is_array($d)) {
                    continue;
                }
                $dn = trim((string) ($d['name'] ?? ''));
                if ($dn === '') {
                    continue;
                }
                $dishes[] = [
                    'name' => $dn,
                    'description' => trim((string) ($d['description'] ?? '')),
                    'category' => trim((string) ($d['category'] ?? '')),
                    'tags' => $this->normalizeDishTags($d['tags'] ?? []),
                ];
            }
            $n = count($dishes);
            $title = $n ? "家常好菜（{$n}道）" : '家常好菜';
            $nameLine = implode('、', array_map(fn ($x) => $x['name'], array_slice($dishes, 0, 4)));
            $summary = Str::limit($nameLine.($n > 4 ? " 等共{$n}道" : ''), 200);
            $this->logFeature('table_menu', 'generate', 'success', $userId, $input, $title, $summary);

            return ['dishes' => $dishes, 'history_saved' => false];
        } catch (\Throwable $e) {
            $this->logFeature('table_menu', 'generate', 'failed', $userId, $input, null, null, Str::limit($e->getMessage(), 500));
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function tableDishRecipe(int $userId, array $body): array
    {
        $name = trim((string) ($body['dish_name'] ?? ''));
        $desc = trim((string) ($body['dish_description'] ?? ''));
        $cat = trim((string) ($body['category'] ?? ''));
        $locale = (string) ($body['locale'] ?? 'zh-CN');
        $input = compact('name', 'desc', 'cat', 'locale');
        $input['dish_name'] = $name;

        $sys = implode("\n", [
            '你是“饭否”小程序的单道菜谱生成器（table-dish-recipe）。',
            '请只输出 JSON，不要输出任何额外解释或 Markdown。',
            'JSON 必须包含字段：',
            '- name: string（菜名）',
            '- ingredients: string[]（食材，建议 6-12 个）',
            '- steps: Array，每个元素包含：',
            '  - step: number（步骤序号，从 1 开始）',
            '  - description: string（步骤说明）',
            '  - time?: number（可选：预计耗时分钟）',
            '  - temperature?: string（可选：温度/火候）',
            '并尽量给出结构化烹饪过程。',
        ]);

        $user = implode("\n", [
            "菜名 name: {$name}",
            "菜品描述 dish_description: {$desc}",
            "类别 category: {$cat}",
            "语言 locale: {$locale}",
            '',
            '输出内容必须是可执行的烹饪步骤；ingredients 与步骤要一致。',
        ]);

        try {
            $jsonObj = $this->chatJsonObject(AiScene::RecipeTextGeneration->value, $sys, $user, 0.7);
            $out = [
                'name' => trim((string) ($jsonObj['name'] ?? $name)) ?: $name,
                'cuisine' => trim((string) ($jsonObj['cuisine'] ?? $cat)) ?: null,
                'ingredients' => $this->normalizeIngredients($jsonObj['ingredients'] ?? []),
                'steps' => is_array($jsonObj['steps'] ?? null) ? $jsonObj['steps'] : [],
                'cookingTime' => isset($jsonObj['cookingTime']) && is_numeric($jsonObj['cookingTime']) ? (float) $jsonObj['cookingTime'] : null,
                'difficulty' => trim((string) ($jsonObj['difficulty'] ?? '')) ?: null,
                'tips' => isset($jsonObj['tips']) && is_array($jsonObj['tips']) ? $this->normalizeStringList($jsonObj['tips'], 6) : null,
            ];
            $summary = Str::limit(implode('、', array_slice($out['ingredients'], 0, 8)), 200);
            $this->logFeature('table_menu', 'dish_recipe', 'success', $userId, $input, $out['name'], $summary);

            return $out;
        } catch (\Throwable $e) {
            $this->logFeature('table_menu', 'dish_recipe', 'failed', $userId, $input, $name, null, Str::limit($e->getMessage(), 500));
            throw $e;
        }
    }

    /**
     * @return array{url: string, raw?: mixed}
     */
    public function recipeImage(string $prompt, ?string $size): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new \InvalidArgumentException('prompt_missing');
        }
        $runtime = $this->resolveEnabledRuntime(AiScene::RecipeImageGeneration->value);
        if ($runtime === null) {
            throw new \RuntimeException('recipe_image_generation_not_configured');
        }
        $baseUrl = rtrim((string) ($runtime['base_url'] ?? ''), '/');
        $apiPath = ltrim((string) (($runtime['model']['api_path'] ?? '/images/generations')), '/');
        $modelCode = (string) ($runtime['model']['model_code'] ?? '');
        $apiKey = (string) ($runtime['api_key'] ?? '');
        $timeoutSec = max(8, (int) ceil(((int) ($runtime['timeout_ms'] ?? 120000)) / 1000));
        $requestUrl = $baseUrl.'/'.$apiPath;
        $payload = [
            'model' => $modelCode,
            'prompt' => $prompt,
            'size' => $size !== null && trim($size) !== '' ? trim($size) : '1024x1024',
        ];
        $resp = Http::timeout($timeoutSec)->acceptJson()->withToken($apiKey)->post($requestUrl, $payload);
        if (! $resp->successful()) {
            throw new \RuntimeException('recipe_image_http_'.$resp->status());
        }
        $data = $resp->json();
        $item = is_array($data['data'] ?? null) ? ($data['data'][0] ?? null) : null;
        $url = '';
        if (is_array($item)) {
            $url = trim((string) ($item['url'] ?? $item['image_url'] ?? ''));
        }
        if ($url === '' && is_string($data['url'] ?? null)) {
            $url = trim((string) $data['url']);
        }
        if ($url === '') {
            throw new \RuntimeException('image_url_missing');
        }

        return ['url' => $url, 'raw' => $data];
    }

    /**
     * @return array{ingredients: list<string>, raw?: mixed}
     */
    public function ingredientsRecognize(int $userId, string $imageBase64): array
    {
        $base64 = trim($imageBase64);
        if ($base64 === '') {
            throw new \InvalidArgumentException('image_base64_missing');
        }

        $systemPrompt = implode("\n", [
            '你是食材识别助手。',
            '请识别图片里的食材，仅返回 JSON。',
            '格式必须为：{"ingredients":["食材1","食材2"]}',
            '不要输出任何额外说明。',
        ]);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image_url',
                        'image_url' => ['url' => 'data:image/jpeg;base64,'.$base64],
                    ],
                    [
                        'type' => 'text',
                        'text' => '识别图片中的食材，返回 JSON。',
                    ],
                ],
            ],
        ];

        $jsonObj = $this->chatJsonObjectFromMessages(AiScene::RecipeTextGeneration->value, $messages, 0.2);
        $ingredients = $this->normalizeIngredients($jsonObj['ingredients'] ?? []);

        return ['ingredients' => array_slice($ingredients, 0, 12)];
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    private function chatJsonObjectFromMessages(string $sceneCode, array $messages, float $temperature): array
    {
        $runtime = $this->resolveEnabledRuntime($sceneCode);
        if ($runtime === null) {
            throw new \RuntimeException('ai_scene_not_configured:'.$sceneCode);
        }
        $baseUrl = rtrim((string) ($runtime['base_url'] ?? ''), '/');
        $apiPath = ltrim((string) (($runtime['model']['api_path'] ?? '/chat/completions')), '/');
        $modelCode = (string) ($runtime['model']['model_code'] ?? '');
        $apiKey = (string) ($runtime['api_key'] ?? '');
        $timeoutSec = max(8, (int) ceil(((int) ($runtime['timeout_ms'] ?? 120000)) / 1000));
        $requestUrl = $baseUrl.'/'.$apiPath;
        $payload = [
            'model' => $modelCode,
            'messages' => $messages,
            'temperature' => $temperature,
        ];
        $parsed = $this->requestAndParseSafe($requestUrl, $payload, $timeoutSec, $apiKey, $runtime['fallback_model_codes'] ?? []);
        if (! is_array($parsed)) {
            throw new \RuntimeException('bigmodel_response_not_json');
        }

        return $parsed;
    }

    /**
     * @return array<string, mixed>
     */
    private function chatJsonObject(string $sceneCode, string $systemPrompt, string $userPrompt, float $temperature): array
    {
        return $this->chatJsonObjectFromMessages($sceneCode, [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ], $temperature);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestAndParseSafe(
        string $requestUrl,
        array $payload,
        int $timeoutSec,
        string $apiKey,
        mixed $fallbackModelCodes,
    ): ?array {
        if ($requestUrl === '' || $apiKey === '') {
            return null;
        }
        try {
            $modelRaw = (string) ($payload['model'] ?? '');
            $cfgFallbacks = [];
            if (is_array($fallbackModelCodes)) {
                foreach ($fallbackModelCodes as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $cfgFallbacks[] = trim($item);
                    }
                }
            }
            if ($cfgFallbacks === []) {
                $cfgFallbacks = ['gpt-5-mini', 'gpt-4o-mini'];
            }
            $modelCandidates = array_values(array_unique(array_filter(
                [$modelRaw, ...$cfgFallbacks],
                fn ($m): bool => is_string($m) && trim($m) !== ''
            )));

            foreach ($modelCandidates as $candidateModel) {
                $candidatePayload = $payload;
                $candidatePayload['model'] = $candidateModel;

                $isResponsesApi = str_ends_with(strtolower($requestUrl), '/responses');
                if ($isResponsesApi) {
                    $messages = is_array($candidatePayload['messages'] ?? null) ? $candidatePayload['messages'] : [];
                    $input = [];
                    foreach ($messages as $msg) {
                        if (! is_array($msg)) {
                            continue;
                        }
                        $role = (string) ($msg['role'] ?? 'user');
                        $content = $msg['content'] ?? '';
                        if (is_array($content)) {
                            $input[] = ['role' => $role !== '' ? $role : 'user', 'content' => $content];

                            continue;
                        }
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

                $resp = Http::timeout($timeoutSec)->acceptJson()->withToken($apiKey)->post($requestUrl, $candidatePayload);
                if (! $resp->successful()) {
                    continue;
                }
                $data = $resp->json();
                $content = $this->extractTextContentFromResponse($data);
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

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function extractTextContentFromResponse(?array $data): ?string
    {
        if (! is_array($data)) {
            return null;
        }
        $chatContent = $data['choices'][0]['message']['content'] ?? null;
        if (is_string($chatContent) && trim($chatContent) !== '') {
            return $chatContent;
        }
        if (is_string($data['output_text'] ?? null) && trim((string) $data['output_text']) !== '') {
            return (string) $data['output_text'];
        }
        if (isset($data['output']) && is_array($data['output'])) {
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
                return implode("\n", $segments);
            }
        }

        return null;
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
     * @return array<string, mixed>|null
     */
    private function resolveEnabledRuntime(string $sceneCode): ?array
    {
        /** @var AiModelConfig|null $config */
        $config = AiModelConfig::query()
            ->with(['provider', 'model'])
            ->where('scene_code', $sceneCode)
            ->where('is_enabled', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        if (! $config || ! $config->provider || ! $config->model) {
            return null;
        }
        if (! $config->provider->is_enabled || ! $config->model->is_enabled) {
            return null;
        }

        return [
            'scene_code' => $sceneCode,
            'model' => [
                'model_code' => $config->model->model_code,
                'api_path' => $config->model->api_path,
            ],
            'base_url' => $config->base_url_override ?: $config->provider->base_url,
            'api_key' => (string) $config->getAttribute('api_key'),
            'temperature' => $config->temperature !== null ? (float) $config->temperature : null,
            'timeout_ms' => $config->timeout_ms,
            'fallback_model_codes' => $this->parseFallbackModelCodes($config->fallback_model_codes),
        ];
    }

    private function parseFallbackModelCodes(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $items = preg_split('/[\r\n,]+/', $raw) ?: [];
        $out = [];
        foreach ($items as $item) {
            $s = trim((string) $item);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $lines
     */
    private function buildFortuneUserPrompt(string $type, array $body, string $locale): string
    {
        if ($type === 'daily') {
            $daily = is_array($body['daily'] ?? null) ? $body['daily'] : [];

            return implode("\n", [
                '占卜类型 type=daily',
                '星座 zodiac='.trim((string) ($daily['zodiac'] ?? '未知')),
                '生肖 animal='.trim((string) ($daily['animal'] ?? '未知')),
                '日期 date='.trim((string) ($daily['date'] ?? now()->toDateString())),
                "语言 locale={$locale}",
                '',
                '请生成一份“今日运势菜”。',
            ]);
        }
        if ($type === 'mood') {
            $mood = is_array($body['mood'] ?? null) ? $body['mood'] : [];
            $moods = is_array($mood['moods'] ?? null) ? array_map('strval', $mood['moods']) : [];
            $intensity = isset($mood['intensity']) && is_numeric($mood['intensity'])
                ? max(1, min(5, (int) round((float) $mood['intensity'])))
                : 3;

            return implode("\n", [
                '占卜类型 type=mood',
                '情绪 moods='.($moods !== [] ? implode('、', $moods) : '未知'),
                "情绪强度 intensity={$intensity}",
                "语言 locale={$locale}",
                '',
                '请生成一份“心情料理”。',
            ]);
        }
        if ($type === 'couple') {
            $couple = is_array($body['couple'] ?? null) ? $body['couple'] : [];
            $u1 = is_array($couple['user1'] ?? null) ? $couple['user1'] : [];
            $u2 = is_array($couple['user2'] ?? null) ? $couple['user2'] : [];
            $p1 = is_array($u1['personality'] ?? null) ? array_map('strval', $u1['personality']) : [];
            $p2 = is_array($u2['personality'] ?? null) ? array_map('strval', $u2['personality']) : [];

            return implode("\n", [
                '占卜类型 type=couple',
                '甲方: zodiac='.trim((string) ($u1['zodiac'] ?? '未知')).' animal='.trim((string) ($u1['animal'] ?? '未知')).' personality='.($p1 !== [] ? implode('、', $p1) : '无'),
                '乙方: zodiac='.trim((string) ($u2['zodiac'] ?? '未知')).' animal='.trim((string) ($u2['animal'] ?? '未知')).' personality='.($p2 !== [] ? implode('、', $p2) : '无'),
                "语言 locale={$locale}",
                '',
                '请生成一份“双人星座默契菜”。',
            ]);
        }
        $number = is_array($body['number'] ?? null) ? $body['number'] : [];
        $n = isset($number['number']) && is_numeric($number['number']) ? (float) $number['number'] : 7.0;
        $isRandom = (bool) ($number['is_random'] ?? false);

        return implode("\n", [
            '占卜类型 type=number',
            '幸运数字 number='.(string) $n,
            '是否随机 is_random='.($isRandom ? 'true' : 'false'),
            "语言 locale={$locale}",
            '',
            '请生成一份“幸运数字菜”。',
        ]);
    }

    /**
     * @param  list<string>  $ingredients
     * @param  array<string, mixed>  $input
     */
    private function logFeature(
        string $featureType,
        string $subType,
        string $status,
        int $userId,
        array $input,
        ?string $title,
        ?string $resultSummary,
        ?string $errorMessage = null,
    ): void {
        try {
            $req = request();
            FeatureDataRecord::query()->create([
                'feature_type' => $featureType,
                'channel' => 'mini_program',
                'user_id' => $userId,
                'status' => $status,
                'title' => $title,
                'sub_type' => $subType,
                'input_payload' => $input,
                'result_summary' => $resultSummary,
                'error_message' => $errorMessage,
                'requested_at' => now(),
                'source_ip' => $req instanceof Request ? $req->ip() : null,
                'user_agent' => $req instanceof Request ? mb_substr((string) $req->userAgent(), 0, 512) : null,
            ]);
        } catch (\Throwable) {
            /* best-effort */
        }
    }

    private function clampInt(mixed $raw, int $min, int $max, int $fallback): int
    {
        $x = is_numeric($raw) ? (float) $raw : NAN;
        if (! is_finite($x)) {
            return $fallback;
        }

        return max($min, min($max, (int) round($x)));
    }

    private function normalizeDifficulty(string $v): string
    {
        return in_array($v, ['easy', 'hard'], true) ? $v : 'medium';
    }

    private function normalizeSauceCategory(string $v): string
    {
        $allowed = ['spicy', 'garlic', 'sweet', 'complex', 'regional', 'fusion'];
        $s = trim($v);

        return in_array($s, $allowed, true) ? $s : 'complex';
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

    /**
     * @return list<string>
     */
    private function normalizeIngredients(mixed $raw): array
    {
        return $this->normalizeStringList($raw, 24);
    }

    /**
     * @return list<string>
     */
    private function normalizeDishTags(mixed $raw): array
    {
        if (is_array($raw)) {
            return $this->normalizeStringList($raw, 8);
        }
        if (is_string($raw) && trim($raw) !== '') {
            return $this->normalizeStringList(preg_split('/[,，、\n]/u', $raw) ?: [], 8);
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function normalizeFortuneSteps(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $s) {
            if (is_string($s) && trim($s) !== '') {
                $out[] = trim($s);
            } elseif (is_array($s) && isset($s['description']) && is_string($s['description'])) {
                $t = trim($s['description']);
                if ($t !== '') {
                    $out[] = $t;
                }
            }
        }

        return array_slice($out, 0, 12);
    }
}
