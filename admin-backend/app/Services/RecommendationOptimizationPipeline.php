<?php

namespace App\Services;

use App\Models\RecommendationSession;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * 编排：画像/状态/历史/反馈 → 打分 → 多样性截断 → 注入模型提示与可解释 trace。
 */
final class RecommendationOptimizationPipeline
{
    public function __construct(
        private readonly RecommendationFeedbackAdjustService $feedbackAdjust,
        private readonly RecommendationScoringService $scoring,
        private readonly RecommendationDiversifyService $diversify,
        private readonly UserPreferenceSignalService $preferenceSignals,
        private readonly RecommendationConfigService $strategy,
    ) {}

    /**
     * initial / reroll 使用：返回 generator_hints 与 context 补丁。
     *
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $normalizedPrefs
     * @return array{
     *   context_patch: array<string, mixed>,
     *   generator_hints: array<string, mixed>|null,
     *   trace: list<array<string, mixed>>
     * }
     */
    public function forSessionMainPick(
        User $user,
        array $aggregatedContext,
        array $normalizedPrefs,
        ?RecommendationSession $session,
        string $mode,
    ): array {
        $today = Carbon::today();
        $signals = $this->feedbackAdjust->buildSignals($user, $today);
        $learned = $this->preferenceSignals->buildEffectiveWeights($user, $today);
        $recentRows = $this->scoring->loadRecentForUser($user, $today);

        $catalog = $this->scoring->loadCatalog();
        $entries = [];
        $trace = [];

        foreach ($catalog as $dish) {
            if (! is_array($dish) || empty($dish['name'])) {
                continue;
            }
            $r = $this->scoring->scoreCatalogDish(
                $dish,
                $aggregatedContext,
                $normalizedPrefs,
                $signals,
                $learned,
                $recentRows,
                $session,
            );
            if ($r['excluded']) {
                $trace[] = ['dish' => $dish['name'], 'excluded' => true, 'explain' => $r['explain']];

                continue;
            }
            $entries[] = [
                'dish' => $dish,
                'score' => $r['score'],
                'explain' => $r['explain'],
            ];
        }

        usort($entries, fn ($a, $b) => $b['score'] <=> $a['score']);

        $lastMain = $session?->last_recommended_dish;
        if ($mode === 'initial') {
            $lastMain = null;
        }
        $trailMax = $this->strategy->int('diversity_control.cuisine_trail_max');
        $cuisineTrail = $this->diversify->recentCuisineTrail($recentRows, $trailMax);

        $primaryPick = $this->strategy->int('diversity_control.primary_pick');
        $altPool = $this->strategy->int('diversity_control.alternative_pool');
        $pool = $this->diversify->buildPools($entries, $lastMain, $cuisineTrail, $primaryPick, $altPool);

        if ($pool['primary_candidates'] === []) {
            return [
                'context_patch' => [
                    'recommendation_optimization' => [
                        'enabled' => false,
                        'note' => 'catalog_all_filtered',
                        'feedback_traces' => $signals['raw_traces'] ?? [],
                    ],
                ],
                'generator_hints' => null,
                'trace' => $trace,
            ];
        }

        $contextPatch = [
            'recommendation_optimization' => [
                'enabled' => true,
                'mode' => $mode,
                'primary_candidates' => $pool['primary_candidates'],
                'alternative_pool' => $pool['alternative_pool'],
                'feedback_signals_summary' => [
                    'boost_flavor_keywords' => array_slice($signals['boost_flavor_keywords'] ?? [], 0, 12),
                    'penalize_flavor_keywords' => array_slice($signals['penalize_flavor_keywords'] ?? [], 0, 12),
                    'complexity_hint' => $signals['complexity_hint'] ?? 'neutral',
                    'temperature_hint' => $signals['temperature_hint'] ?? 'neutral',
                ],
                'explain_style_note' => $signals['explain_style_note'] ?? null,
                'feedback_traces' => array_slice($signals['raw_traces'] ?? [], 0, 24),
                'top_scored_preview' => array_slice(array_map(
                    fn ($e) => [
                        'name' => $e['dish']['name'] ?? '',
                        'score' => round($e['score'], 2),
                        'flavor_tags' => $e['dish']['flavor_tags'] ?? [],
                        'cuisine_type' => $e['dish']['cuisine_type'] ?? '',
                        'complexity' => $e['dish']['cooking_complexity'] ?? '',
                        'explain' => array_slice($e['explain'] ?? [], 0, 8),
                        'diversify' => $e['diversify'] ?? [],
                    ],
                    $pool['ordered']
                ), 0, 12),
            ],
        ];

        $generatorHints = [
            'primary_candidates' => $pool['primary_candidates'],
            'alternative_pool' => $pool['alternative_pool'],
            'explain_style_note' => $signals['explain_style_note'] ?? null,
            'complexity_hint' => $signals['complexity_hint'] ?? 'neutral',
        ];

        return [
            'context_patch' => $contextPatch,
            'generator_hints' => $generatorHints,
            'trace' => $trace,
        ];
    }

    /**
     * 备选切换：主菜已定，仅生成替代候选池（不含主菜、不含已排除）。
     *
     * @param  array<string, mixed>  $aggregatedContext
     * @param  array{taste?: string, avoid?: string, people?: int|null}  $normalizedPrefs
     * @param  list<string>  $excludedPastMains
     * @return array{context_patch: array<string, mixed>, generator_hints: array<string, mixed>|null}
     */
    public function forAlternativePool(
        User $user,
        array $aggregatedContext,
        array $normalizedPrefs,
        string $lockedMainDish,
        array $excludedPastMains,
    ): array {
        $today = Carbon::today();
        $signals = $this->feedbackAdjust->buildSignals($user, $today);
        $learned = $this->preferenceSignals->buildEffectiveWeights($user, $today);
        $recentRows = $this->scoring->loadRecentForUser($user, $today);

        $catalog = $this->scoring->loadCatalog();
        $entries = [];

        foreach ($catalog as $dish) {
            if (! is_array($dish) || empty($dish['name'])) {
                continue;
            }
            $name = (string) $dish['name'];
            if (RecommendationSession::dishKey($name) === RecommendationSession::dishKey($lockedMainDish)) {
                continue;
            }
            if (RecommendationSession::isExcluded($name, $excludedPastMains)) {
                continue;
            }
            $r = $this->scoring->scoreCatalogDish(
                $dish,
                $aggregatedContext,
                $normalizedPrefs,
                $signals,
                $learned,
                $recentRows,
                null,
            );
            if ($r['excluded']) {
                continue;
            }
            $entries[] = ['dish' => $dish, 'score' => $r['score'], 'explain' => $r['explain']];
        }

        usort($entries, fn ($a, $b) => $b['score'] <=> $a['score']);

        foreach ($entries as &$e) {
            $div = [];
            $name = (string) ($e['dish']['name'] ?? '');
            if ($this->diversify->nearDuplicateName($name, $lockedMainDish)) {
                $e['score'] -= $this->strategy->float('diversity_control.near_locked_main_penalty');
                $div[] = 'near_locked_main';
            }
            $e['diversify'] = $div;
        }
        unset($e);
        usort($entries, fn ($a, $b) => $b['score'] <=> $a['score']);

        $alts = [];
        $seen = [];
        foreach ($entries as $e) {
            $name = (string) ($e['dish']['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $k = RecommendationSession::dishKey($name);
            if (isset($seen[$k])) {
                continue;
            }
            if (RecommendationSession::dishKey($name) === RecommendationSession::dishKey($lockedMainDish)) {
                continue;
            }
            if (RecommendationSession::isExcluded($name, $excludedPastMains)) {
                continue;
            }
            $alts[] = $name;
            $seen[$k] = true;
            if (count($alts) >= $this->strategy->int('diversity_control.alternative_only_pool_max')) {
                break;
            }
        }

        if ($alts === []) {
            return [
                'context_patch' => [
                    'recommendation_optimization' => [
                        'enabled' => false,
                        'note' => 'alt_pool_empty',
                    ],
                ],
                'generator_hints' => null,
            ];
        }

        return [
            'context_patch' => [
                'recommendation_optimization' => [
                    'enabled' => true,
                    'mode' => 'alternative',
                    'locked_main' => $lockedMainDish,
                    'alternative_pool' => $alts,
                    'feedback_signals_summary' => [
                        'penalize_flavor_keywords' => array_slice($signals['penalize_flavor_keywords'] ?? [], 0, 10),
                    ],
                    'explain_style_note' => $signals['explain_style_note'] ?? null,
                ],
            ],
            'generator_hints' => [
                'primary_candidates' => [$lockedMainDish],
                'alternative_pool' => $alts,
                'explain_style_note' => $signals['explain_style_note'] ?? null,
                'alternative_only' => true,
            ],
        ];
    }
}
