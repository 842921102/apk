<?php

namespace App\Services;

use App\Models\RecommendationRecord;
use App\Models\RecommendationSession;
use App\Models\User;
use App\Support\PreferenceSignalType;
use Illuminate\Support\Carbon;

/**
 * MVP 规则打分：可解释、可调试；候选来自 config 菜品池；权重由 RecommendationConfigService 提供。
 */
final class RecommendationScoringService
{
    public function __construct(
        private readonly RecommendationConfigService $strategy,
    ) {}

    /**
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $sessionPreferences
     * @param  array<string, mixed>  $feedbackSignals  RecommendationFeedbackAdjustService::buildSignals
     * @param  array<string, mixed>  $learnedWeights  UserPreferenceSignalService::buildEffectiveWeights
     * @param  list<array<string, mixed>>  $recentRecommendationRows  from DB
     * @return array{score: float, excluded: bool, explain: list<string>}
     */
    public function scoreCatalogDish(
        array $dish,
        array $aggregatedContext,
        array $sessionPreferences,
        array $feedbackSignals,
        array $learnedWeights,
        array $recentRecommendationRows,
        ?RecommendationSession $session,
    ): array {
        $explain = [];
        $score = $this->strategy->float('scoring_core.base_score');

        $name = (string) ($dish['name'] ?? '');
        $ingredients = array_map('strval', $dish['ingredients'] ?? []);
        $flavorTags = array_map('strval', $dish['flavor_tags'] ?? []);
        $cuisineType = (string) ($dish['cuisine_type'] ?? '');
        $complexity = (string) ($dish['cooking_complexity'] ?? 'medium');
        $scene = (string) ($dish['suitable_scene'] ?? 'couple');
        $healthTags = array_map('strval', $dish['health_tags'] ?? []);
        $tempTags = array_map('strval', $dish['temperature_tags'] ?? []);

        $blob = mb_strtolower($name.implode('', $ingredients).implode('', $flavorTags));

        if ($this->strategy->switchEnabled('enable_taboo_filter')
            && $this->hardExcludeFromTaboo($aggregatedContext, $sessionPreferences, $ingredients, $blob, $explain)) {
            return ['score' => $this->strategy->float('scoring_core.exclude_score'), 'excluded' => true, 'explain' => $explain];
        }

        $profile = $aggregatedContext['user_profile'] ?? [];
        $daily = $aggregatedContext['daily_status'] ?? [];

        if ($this->strategy->switchEnabled('enable_flavor_preference')) {
            $this->scoreFlavorPreference($sessionPreferences, $profile, $flavorTags, $blob, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_diet_goal')) {
            $this->scoreDietGoal($profile, $healthTags, $dish, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_daily_status')) {
            $this->scoreDailyStatus($daily, $tempTags, $flavorTags, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_recent_similar')) {
            $this->scoreRecentSimilar($recentRecommendationRows, $cuisineType, $ingredients, $name, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_feedback_scoring')) {
            $this->scoreFeedbackSignals($feedbackSignals, $blob, $cuisineType, $ingredients, $name, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_favorites_overlap')) {
            $this->scoreFavoritesOverlap($feedbackSignals, $ingredients, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_learned_signals')) {
            $this->scoreUserPreferenceSignals(
                $learnedWeights,
                $blob,
                $cuisineType,
                $ingredients,
                $name,
                $healthTags,
                $flavorTags,
                $complexity,
                $scene,
                $score,
                $explain,
            );
        }
        if ($this->strategy->switchEnabled('enable_learned_diversity_guard')) {
            $this->scoreLearnedDiversityGuard($learnedWeights, $cuisineType, $recentRecommendationRows, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_cooking_frequency')) {
            $this->scoreCookingFrequency($profile, $complexity, $score, $explain);
        }
        if ($this->strategy->switchEnabled('enable_family_scene')) {
            $this->scoreFamilyScene($profile, $sessionPreferences, $scene, $score, $explain);
        }

        if ($session instanceof RecommendationSession && RecommendationSession::isExcluded($name, $session->excluded_dishes ?? [])) {
            $explain[] = 'session_excluded';
            $score = $this->strategy->float('scoring_core.exclude_score');

            return ['score' => $score, 'excluded' => true, 'explain' => $explain];
        }

        return ['score' => $score, 'excluded' => false, 'explain' => $explain];
    }

    /**
     * @param  list<string>  $ingredients
     * @param  list<string>  $explain
     */
    private function hardExcludeFromTaboo(array $ctx, array $prefs, array $ingredients, string $blob, array &$explain): bool
    {
        $profile = $ctx['user_profile'] ?? [];
        $avoidText = mb_strtolower(trim((string) ($prefs['avoid'] ?? '')));
        $taboo = array_merge(
            $this->tokensFromList($profile['taboo_ingredients'] ?? []),
            $this->tokensFromList($profile['dislike_ingredients'] ?? []),
            $this->tokensFromList($profile['allergy_ingredients'] ?? []),
        );
        if ($avoidText !== '') {
            foreach (preg_split('/[,，\s、]+/u', $avoidText) ?: [] as $t) {
                $s = mb_strtolower(trim((string) $t));
                if (mb_strlen($s) >= 2) {
                    $taboo[] = $s;
                }
            }
        }
        $taboo = array_values(array_unique(array_filter($taboo)));
        foreach ($taboo as $tok) {
            if ($tok === '') {
                continue;
            }
            foreach ($ingredients as $ing) {
                if (mb_strpos(mb_strtolower($ing), $tok) !== false || mb_strpos($tok, mb_strtolower($ing)) !== false) {
                    $explain[] = 'filter_taboo:'.$tok;

                    return true;
                }
            }
            if (mb_strpos($blob, $tok) !== false) {
                $explain[] = 'filter_taboo_blob:'.$tok;

                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<mixed>  $list
     * @return list<string>
     */
    private function tokensFromList(array $list): array
    {
        $out = [];
        foreach ($list as $x) {
            $s = mb_strtolower(trim((string) $x));
            if (mb_strlen($s) >= 2) {
                $out[] = $s;
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $flavorTags
     * @param  list<string>  $explain
     */
    private function scoreFlavorPreference(
        array $sessionPreferences,
        array $profile,
        array $flavorTags,
        string $blob,
        float &$score,
        array &$explain,
    ): void {
        $pref = mb_strtolower(trim((string) ($sessionPreferences['taste'] ?? '')));
        $flavors = array_merge(
            $this->tokensFromList($profile['flavor_preferences'] ?? []),
            $pref !== '' ? preg_split('/[,，\s、]+/u', $pref) ?: [] : [],
        );
        foreach ($flavors as $kw) {
            $kw = mb_strtolower(trim((string) $kw));
            if (mb_strlen($kw) < 2) {
                continue;
            }
            if (mb_strpos($blob, $kw) !== false) {
                $score += $this->strategy->float('tag_weights.flavor_pref_hit');
                $explain[] = 'flavor_pref_hit:'.$kw;
            }
        }
    }

    /**
     * @param  list<string>  $healthTags
     * @param  list<string>  $explain
     */
    private function scoreDietGoal(array $profile, array $healthTags, array $dish, float &$score, array &$explain): void
    {
        $goals = [];
        foreach ($profile['diet_goal'] ?? [] as $g) {
            $goals[] = mb_strtolower(trim((string) $g));
        }
        $hg = $profile['health_goal'] ?? null;
        if (is_string($hg) && trim($hg) !== '') {
            $goals[] = mb_strtolower(trim($hg));
        }
        if ($goals === []) {
            return;
        }
        $blob = mb_strtolower(implode('', $healthTags));
        foreach ($goals as $g) {
            if ($g === '') {
                continue;
            }
            if (mb_strpos($blob, $g) !== false || $this->goalAlign($g, $healthTags, $dish)) {
                $score += $this->strategy->float('tag_weights.diet_goal_hit');
                $explain[] = 'diet_goal_hit:'.$g;
            }
        }
    }

    /**
     * @param  list<string>  $healthTags
     */
    private function goalAlign(string $goal, array $healthTags, array $dish): bool
    {
        if (str_contains($goal, '脂') || str_contains($goal, '瘦')) {
            foreach ($healthTags as $h) {
                if (str_contains($h, '低脂')) {
                    return true;
                }
            }
        }
        if (str_contains($goal, '蛋白')) {
            foreach ($healthTags as $h) {
                if (str_contains($h, '高蛋白')) {
                    return true;
                }
            }
        }
        $comp = (string) ($dish['cooking_complexity'] ?? '');
        if (str_contains($goal, '清淡') && $comp === 'quick') {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>  $flavorTags
     * @param  list<string>  $explain
     */
    private function scoreDailyStatus(array $daily, array $tempTags, array $flavorTags, float &$score, array &$explain): void
    {
        if (empty($daily['has_record'])) {
            return;
        }
        $wanted = mb_strtolower((string) ($daily['wanted_food_style'] ?? ''));
        $body = mb_strtolower((string) ($daily['body_state'] ?? ''));
        $mood = mb_strtolower((string) ($daily['mood'] ?? ''));

        $map = [
            'spicy' => ['麻辣', '香辣', '辣'],
            'hot' => ['暖胃', '热', '汤'],
            'light' => ['清淡', '清爽'],
            'comforting' => ['家常', '暖胃'],
            'quick' => ['快手'],
        ];
        foreach ($map as $key => $kws) {
            if (str_contains($wanted, $key)) {
                foreach ($kws as $kw) {
                    if ($this->listOrBlobHas(array_merge($flavorTags, $tempTags), $kw)) {
                        $score += $this->strategy->float('tag_weights.daily_wanted');
                        $explain[] = 'daily_wanted:'.$key;
                        break 2;
                    }
                }
            }
        }
        if (str_contains($body, 'tired') || str_contains($body, 'low_appetite')) {
            foreach ($tempTags as $t) {
                if (str_contains(mb_strtolower($t), 'warm') || str_contains($t, '暖')) {
                    $score += $this->strategy->float('tag_weights.daily_body_warm');
                    $explain[] = 'daily_body_warm';
                    break;
                }
            }
        }
        if (str_contains($mood, 'stressed')) {
            if ($this->listOrBlobHas($flavorTags, '清淡')) {
                $score += $this->strategy->float('tag_weights.daily_mood_light');
                $explain[] = 'daily_mood_light';
            }
        }
    }

    /**
     * @param  list<string|string>  $rows  serialized recent recommendation records
     * @param  list<string>  $ingredients
     * @param  list<string>  $explain
     */
    private function scoreRecentSimilar(array $rows, string $cuisineType, array $ingredients, string $name, float &$score, array &$explain): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $otherIng = array_map('strval', $row['ingredients'] ?? []);
            $overlap = $this->ingredientOverlap($ingredients, $otherIng);
            $overlapMin = $this->strategy->int('recent_similarity.overlap_min_count');
            if ($overlap >= $overlapMin) {
                $score += $this->strategy->float('recent_similarity.ingredient_overlap_penalty');
                $explain[] = 'recent_similar_ingredients:'.(string) $overlap;
            }
            $oc = mb_strtolower((string) ($row['cuisine_type'] ?? $row['cuisine'] ?? ''));
            if ($oc !== '' && $oc === mb_strtolower($cuisineType)) {
                $score += $this->strategy->float('recent_similarity.same_cuisine_penalty');
                $explain[] = 'recent_same_cuisine';
            }
            if (isset($row['recommended_dish']) && RecommendationSession::dishKey((string) $row['recommended_dish']) === RecommendationSession::dishKey($name)) {
                $score += $this->strategy->float('recent_similarity.same_dish_penalty');
                $explain[] = 'recent_same_dish';
            }
        }
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private function ingredientOverlap(array $a, array $b): int
    {
        $bk = [];
        foreach ($b as $x) {
            $bk[RecommendationSession::dishKey($x)] = true;
        }
        $n = 0;
        foreach ($a as $x) {
            if (isset($bk[RecommendationSession::dishKey($x)])) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param  list<string>  $ingredients
     * @param  list<string>  $explain
     */
    private function scoreFeedbackSignals(
        array $signals,
        string $blob,
        string $cuisineType,
        array $ingredients,
        string $name,
        float &$score,
        array &$explain,
    ): void {
        foreach ($signals['boost_flavor_keywords'] ?? [] as $kw) {
            $kw = (string) $kw;
            if ($kw !== '' && mb_strpos($blob, $kw) !== false) {
                $score += $this->strategy->float('feedback_apply_weights.boost_flavor');
                $explain[] = 'fb_boost_flavor:'.$kw;
            }
        }
        foreach ($signals['penalize_flavor_keywords'] ?? [] as $kw) {
            $kw = (string) $kw;
            if ($kw !== '' && mb_strpos($blob, $kw) !== false) {
                $score += $this->strategy->float('feedback_apply_weights.pen_flavor');
                $explain[] = 'fb_pen_flavor:'.$kw;
            }
        }
        foreach ($signals['boost_cuisine_types'] ?? [] as $c) {
            if ($this->cuisineLooseMatch((string) $c, $cuisineType)) {
                $score += $this->strategy->float('feedback_apply_weights.boost_cuisine');
                $explain[] = 'fb_boost_cuisine';
            }
        }
        foreach ($signals['penalize_cuisine_types'] ?? [] as $c) {
            if ($this->cuisineLooseMatch((string) $c, $cuisineType)) {
                $score += $this->strategy->float('feedback_apply_weights.pen_cuisine');
                $explain[] = 'fb_pen_cuisine';
            }
        }
        foreach ($signals['penalize_ingredient_tokens'] ?? [] as $tok) {
            foreach ($ingredients as $ing) {
                if (mb_strpos(mb_strtolower($ing), (string) $tok) !== false) {
                    $score += $this->strategy->float('feedback_apply_weights.pen_ingredient');
                    $explain[] = 'fb_pen_ing:'.(string) $tok;
                    break;
                }
            }
        }
        foreach ($signals['penalize_dish_name_tokens'] ?? [] as $tok) {
            if ($tok !== '' && RecommendationSession::dishKey($name) === RecommendationSession::dishKey((string) $tok)) {
                $score += $this->strategy->float('feedback_apply_weights.pen_same_dish_name');
                $explain[] = 'fb_pen_same_dish_name';
            }
        }

        $tempHint = (string) ($signals['temperature_hint'] ?? 'neutral');
        if ($tempHint === 'prefer_warm') {
            foreach (['cold', '凉'] as $t) {
                if (mb_strpos($blob, $t) !== false) {
                    $score += $this->strategy->float('feedback_apply_weights.temp_pen_cold');
                    $explain[] = 'fb_temp_pen_cold';
                    break;
                }
            }
        }
        if ($tempHint === 'prefer_cold') {
            if (str_contains($blob, 'warm') || str_contains($blob, '热') || str_contains($blob, '暖')) {
                $score += $this->strategy->float('feedback_apply_weights.temp_pen_warm');
                $explain[] = 'fb_temp_pen_warm';
            }
        }
    }

    private function cuisineLooseMatch(string $hint, string $catalogCode): bool
    {
        $h = mb_strtolower(trim($hint));
        $c = mb_strtolower(trim($catalogCode));
        if ($h === '' || $c === '') {
            return false;
        }
        if (mb_strpos($h, $c) !== false || mb_strpos($c, $h) !== false) {
            return true;
        }
        $aliases = [
            '川' => ['sichuan'],
            '粤' => ['cantonese'],
            '家常' => ['home'],
            '沪' => ['shanghai'],
            '江浙' => ['jiangsu'],
            '湘' => ['hunan'],
            '鲁' => ['shandong'],
            '京' => ['beijing'],
            '日本' => ['japanese'],
            '泰' => ['thai'],
            '西' => ['western'],
        ];
        foreach ($aliases as $ch => $codes) {
            if (mb_strpos($h, $ch) !== false && in_array($c, $codes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $ingredients
     * @param  list<string>  $explain
     */
    private function scoreFavoritesOverlap(array $signals, array $ingredients, float &$score, array &$explain): void
    {
        $n = 0;
        foreach ($signals['boost_flavor_keywords'] ?? [] as $kw) {
            foreach ($ingredients as $ing) {
                if (mb_strpos(mb_strtolower($ing), (string) $kw) !== false) {
                    $n++;
                    break;
                }
            }
        }
        if ($n >= $this->strategy->int('favorites_overlap.min_keyword_hits')) {
            $score += $this->strategy->float('favorites_overlap.bonus');
            $explain[] = 'fav_ingredient_overlap';
        }
    }

    /**
     * 行为学习层：在静态画像与短期反馈信号之后叠加长期衰减权重。
     *
     * @param  array<string, mixed>  $learned
     * @param  list<string>  $ingredients
     * @param  list<string>  $healthTags
     * @param  list<string>  $flavorTags
     * @param  list<string>  $explain
     */
    private function scoreUserPreferenceSignals(
        array $learned,
        string $blob,
        string $cuisineType,
        array $ingredients,
        string $name,
        array $healthTags,
        array $flavorTags,
        string $complexity,
        string $scene,
        float &$score,
        array &$explain,
    ): void {
        $dishKey = RecommendationSession::dishKey($name);
        $dishMap = $learned[PreferenceSignalType::Dish] ?? [];
        if (is_array($dishMap) && $dishKey !== '' && isset($dishMap[$dishKey])) {
            $adj = $this->scaleLearnedDelta((float) $dishMap[$dishKey]);
            if (abs($adj) >= $this->strategy->float('learned_weights.dish_abs_threshold')) {
                $score += $adj;
                $explain[] = 'learned_dish:'.round($adj, 2);
            }
        }

        $cMap = $learned[PreferenceSignalType::Cuisine] ?? [];
        if (is_array($cMap)) {
            foreach ($cMap as $hint => $w) {
                if (! is_string($hint) || ! $this->cuisineLooseMatch($hint, $cuisineType)) {
                    continue;
                }
                $adj = $this->scaleLearnedDelta((float) $w);
                if (abs($adj) >= $this->strategy->float('learned_weights.cuisine_abs_threshold')) {
                    $score += $adj;
                    $explain[] = 'learned_cuisine:'.round($adj, 2);
                    break;
                }
            }
        }

        $fMap = $learned[PreferenceSignalType::Flavor] ?? [];
        if (is_array($fMap)) {
            $flavorMult = $this->strategy->float('learned_weights.flavor_mult');
            $flavorTh = $this->strategy->float('learned_weights.flavor_abs_threshold');
            foreach ($fMap as $kw => $w) {
                if (! is_string($kw) || $kw === '') {
                    continue;
                }
                if (mb_strpos($blob, $kw) === false && ! $this->listOrBlobHas($flavorTags, $kw)) {
                    continue;
                }
                $adj = $this->scaleLearnedDelta((float) $w);
                if (abs($adj) < $flavorTh) {
                    continue;
                }
                $score += $adj * $flavorMult;
                $explain[] = 'learned_flavor:'.round($adj * $flavorMult, 2).':'.$kw;
            }
        }

        $hMap = $learned[PreferenceSignalType::HealthTag] ?? [];
        if (is_array($hMap)) {
            foreach ($hMap as $tag => $w) {
                if (! is_string($tag)) {
                    continue;
                }
                foreach ($healthTags as $ht) {
                    if (mb_strpos(mb_strtolower((string) $ht), $tag) !== false || mb_strpos($tag, mb_strtolower((string) $ht)) !== false) {
                        $adj = $this->scaleLearnedDelta((float) $w);
                        if (abs($adj) >= $this->strategy->float('learned_weights.health_abs_threshold')) {
                            $score += $adj;
                            $explain[] = 'learned_health:'.round($adj, 2);
                        }
                        break;
                    }
                }
            }
        }

        $cxMap = $learned[PreferenceSignalType::CookingComplexity] ?? [];
        if (is_array($cxMap) && $complexity !== '') {
            foreach ($cxMap as $k => $w) {
                if (! is_string($k)) {
                    continue;
                }
                if (mb_strtolower($k) === mb_strtolower($complexity)) {
                    $adj = $this->scaleLearnedDelta((float) $w);
                    if (abs($adj) >= $this->strategy->float('learned_weights.complexity_abs_threshold')) {
                        $score += $adj;
                        $explain[] = 'learned_complexity:'.round($adj, 2);
                    }
                    break;
                }
            }
        }

        $sceneMap = $learned[PreferenceSignalType::Scene] ?? [];
        if (is_array($sceneMap) && $scene !== '') {
            foreach ($sceneMap as $k => $w) {
                if (! is_string($k)) {
                    continue;
                }
                if (mb_strtolower($k) === mb_strtolower($scene)) {
                    $adj = $this->scaleLearnedDelta((float) $w);
                    if (abs($adj) >= $this->strategy->float('learned_weights.scene_abs_threshold')) {
                        $score += $adj;
                        $explain[] = 'learned_scene:'.round($adj, 2);
                    }
                    break;
                }
            }
        }

        $moodMap = $learned[PreferenceSignalType::MoodTag] ?? [];
        if (is_array($moodMap) && $dishKey !== '') {
            $today = Carbon::today();
            foreach ($moodMap as $fullKey => $w) {
                if (! is_string($fullKey) || ! str_starts_with($fullKey, 'not_today:')) {
                    continue;
                }
                $rest = substr($fullKey, strlen('not_today:'));
                $colonPos = strpos($rest, ':');
                if ($colonPos === false) {
                    continue;
                }
                $dateStr = substr($rest, 0, $colonPos);
                $storedDk = substr($rest, $colonPos + 1);
                if ($storedDk === '' || $storedDk !== $dishKey) {
                    continue;
                }
                try {
                    $eventDay = Carbon::parse($dateStr)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }
                if ($today->lt($eventDay)) {
                    continue;
                }
                $daysSince = (int) $eventDay->diffInDays($today);
                if ($daysSince < 1) {
                    continue;
                }
                $adj = $this->scaleLearnedDelta((float) $w);
                $multFirst = $this->strategy->float('learned_weights.not_today_mult_first_day');
                $multDecay = $this->strategy->float('learned_weights.not_today_mult_decay_per_day');
                $multFloor = $this->strategy->float('learned_weights.not_today_mult_floor');
                $mult = $daysSince === 1 ? $multFirst : max($multFloor, $multFirst - ($daysSince - 1) * $multDecay);
                $delta = $adj * $mult;
                if (abs($delta) >= $this->strategy->float('learned_weights.not_today_followup_abs_threshold')) {
                    $score += $delta;
                    $explain[] = 'learned_not_today_next_day:'.round($delta, 2);
                }
            }
        }
    }

    /**
     * 多样性保护：强正向菜系偏好 + 近期连续同菜系记录 → 略降权，避免越推越窄。
     *
     * @param  array<string, mixed>  $learned
     * @param  list<array<string, mixed>>  $recentRows
     * @param  list<string>  $explain
     */
    private function scoreLearnedDiversityGuard(
        array $learned,
        string $cuisineType,
        array $recentRows,
        float &$score,
        array &$explain,
    ): void {
        $cMap = $learned[PreferenceSignalType::Cuisine] ?? [];
        if (! is_array($cMap) || $cuisineType === '') {
            return;
        }
        $pull = 0.0;
        foreach ($cMap as $hint => $w) {
            if (! is_string($hint) || ! is_numeric($w)) {
                continue;
            }
            $wf = (float) $w;
            if ($wf <= 0) {
                continue;
            }
            if ($this->cuisineLooseMatch($hint, $cuisineType)) {
                $pull += $wf;
            }
        }
        if ($pull < $this->strategy->float('diversity_guard.cuisine_pull_threshold')) {
            return;
        }
        $sameStreak = 0;
        foreach ($recentRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $oc = mb_strtolower((string) ($row['cuisine_type'] ?? $row['cuisine'] ?? ''));
            if ($oc === '') {
                continue;
            }
            if ($this->cuisineLooseMatch($oc, $cuisineType)) {
                $sameStreak++;
            } else {
                break;
            }
        }
        if ($sameStreak < $this->strategy->int('diversity_guard.same_cuisine_streak_min')) {
            return;
        }
        $cap = $this->strategy->float('diversity_guard.penalty_cap');
        $penalty = min(
            $cap,
            $this->strategy->float('diversity_guard.penalty_base')
                + $pull * $this->strategy->float('diversity_guard.penalty_pull_mult')
                + $sameStreak * $this->strategy->float('diversity_guard.penalty_streak_mult'),
        );
        $score -= $penalty;
        $explain[] = 'learned_diversity_guard:-'.round($penalty, 2);
    }

    private function scaleLearnedDelta(float $effectiveWeight): float
    {
        $cap = $this->strategy->float('learned_weights.scale_cap');
        $mult = $this->strategy->float('learned_weights.scale_mult');

        return max(-$cap, min($cap, $effectiveWeight * $mult));
    }

    /**
     * @param  list<string>  $explain
     */
    private function scoreCookingFrequency(array $profile, string $complexity, float &$score, array &$explain): void
    {
        $freq = (string) ($profile['cooking_frequency'] ?? '');
        if ($freq === '') {
            return;
        }
        if (str_contains($freq, 'rare') || str_contains($freq, 'low')) {
            if ($complexity === 'heavy') {
                $score += $this->strategy->float('cooking_scene.freq_rare_heavy_penalty');
                $explain[] = 'freq_rare_vs_heavy';
            }
        }
        if (str_contains($freq, 'daily') && $complexity === 'quick') {
            $score += $this->strategy->float('cooking_scene.freq_daily_quick_bonus');
            $explain[] = 'freq_daily_quick_ok';
        }
    }

    /**
     * @param  list<string>  $explain
     */
    private function scoreFamilyScene(array $profile, array $prefs, string $scene, float &$score, array &$explain): void
    {
        $fs = $profile['family_size'] ?? null;
        $n = is_numeric($fs) ? (int) $fs : null;
        $people = isset($prefs['people']) && is_numeric($prefs['people']) ? (int) $prefs['people'] : null;

        if ($n !== null && $n >= 4 && $scene === 'solo') {
            $score += $this->strategy->float('cooking_scene.family_mismatch_solo_penalty');
            $explain[] = 'scene_family_mismatch';
        }
        if ($n !== null && $n <= 2 && $scene === 'family' && ($people === null || $people <= 2)) {
            $score += $this->strategy->float('cooking_scene.small_family_portions_penalty');
            $explain[] = 'scene_solo_portions';
        }
    }

    /**
     * @param  list<string>  $list
     */
    private function listOrBlobHas(array $list, string $kw): bool
    {
        foreach ($list as $x) {
            if (mb_strpos(mb_strtolower((string) $x), $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{name: string, cuisine_type?: string|null, ingredients?: list<string>, recommended_dish?: string, cuisine?: string}>
     */
    public function loadRecentForUser(User $user, Carbon $today): array
    {
        $days = $this->strategy->int('diversity_control.recent_rows_lookback_days');
        $limit = $this->strategy->int('diversity_control.recent_rows_limit');
        $cut = $today->copy()->subDays($days);
        $rows = RecommendationRecord::query()
            ->where('user_id', $user->id)
            ->where('recommendation_date', '>=', $cut->toDateString())
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'recommended_dish' => $r->recommended_dish,
                'cuisine' => $r->cuisine,
                'cuisine_type' => $r->cuisine,
                'ingredients' => is_array($r->ingredients) ? $r->ingredients : [],
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function loadCatalog(): array
    {
        $raw = config('recommendation_dish_catalog', []);
        if (! is_array($raw)) {
            return [];
        }

        return $raw;
    }
}
