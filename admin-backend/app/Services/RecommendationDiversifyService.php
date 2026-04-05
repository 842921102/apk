<?php

namespace App\Services;

use App\Models\RecommendationSession;

/**
 * 去重与多样性：在已打分排序的候选上调整顺序与子集截断。
 */
final class RecommendationDiversifyService
{
    public function __construct(
        private readonly RecommendationConfigService $strategy,
    ) {}

    /**
     * @param  list<array{dish: array<string, mixed>, score: float, explain: list<string>}>  $entries  已按 score 降序
     * @param  list<string>  $recentCuisineTypes  最近主菜菜系（小写 code 或中文均可）
     * @return array{
     *   ordered: list<array{dish: array<string, mixed>, score: float, explain: list<string>, diversify: list<string>}>,
     *   primary_candidates: list<string>,
     *   alternative_pool: list<string>
     * }
     */
    public function buildPools(
        array $entries,
        ?string $lastMainDish,
        array $recentCuisineTypes,
        int $primaryPick = 5,
        int $alternativePool = 16,
    ): array {
        foreach ($entries as &$e) {
            $div = [];
            $name = (string) ($e['dish']['name'] ?? '');
            $cuisine = mb_strtolower((string) ($e['dish']['cuisine_type'] ?? ''));
            if ($lastMainDish !== null && $lastMainDish !== '' && $this->nearDuplicateName($name, $lastMainDish)) {
                $e['score'] -= $this->strategy->float('diversity_control.near_dup_last_main_penalty');
                $div[] = 'near_dup_last_main';
            }
            foreach ($recentCuisineTypes as $rc) {
                if ($rc !== '' && $this->cuisineEcho($cuisine, (string) $rc)) {
                    $e['score'] -= $this->strategy->float('diversity_control.recent_cuisine_echo_penalty');
                    $div[] = 'recent_cuisine_echo';
                    break;
                }
            }
            $e['diversify'] = $div;
        }
        unset($e);

        usort($entries, fn ($a, $b) => $b['score'] <=> $a['score']);

        $primary = [];
        $seen = [];
        $primaryCuisine = [];

        foreach ($entries as $e) {
            if (count($primary) >= $primaryPick) {
                break;
            }
            $name = (string) ($e['dish']['name'] ?? '');
            $k = RecommendationSession::dishKey($name);
            if ($k === '' || isset($seen[$k])) {
                continue;
            }
            $cuisine = (string) ($e['dish']['cuisine_type'] ?? '');
            if (($primaryCuisine[$cuisine] ?? 0) >= $this->strategy->int('diversity_control.primary_max_per_cuisine')) {
                continue;
            }
            $primary[] = $name;
            $seen[$k] = true;
            $primaryCuisine[$cuisine] = ($primaryCuisine[$cuisine] ?? 0) + 1;
        }

        $alts = [];
        $altCuisine = [];

        foreach ($entries as $e) {
            if (count($alts) >= $alternativePool) {
                break;
            }
            $name = (string) ($e['dish']['name'] ?? '');
            $k = RecommendationSession::dishKey($name);
            if ($k === '' || isset($seen[$k])) {
                continue;
            }
            $cuisine = (string) ($e['dish']['cuisine_type'] ?? '');
            if (($altCuisine[$cuisine] ?? 0) >= $this->strategy->int('diversity_control.alternative_max_per_cuisine')) {
                continue;
            }
            $alts[] = $name;
            $seen[$k] = true;
            $altCuisine[$cuisine] = ($altCuisine[$cuisine] ?? 0) + 1;
        }

        if ($primary === []) {
            foreach ($entries as $e) {
                $name = (string) ($e['dish']['name'] ?? '');
                if ($name !== '') {
                    $primary[] = $name;
                    break;
                }
            }
        }

        return [
            'ordered' => $entries,
            'primary_candidates' => $primary,
            'alternative_pool' => $alts,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $recentRows  RecommendationScoringService::loadRecentForUser
     * @return list<string>
     */
    public function recentCuisineTrail(array $recentRows, int $max = 6): array
    {
        $out = [];
        foreach (array_slice($recentRows, 0, $max) as $row) {
            $c = $row['cuisine'] ?? $row['cuisine_type'] ?? '';
            if (is_string($c) && trim($c) !== '') {
                $out[] = mb_strtolower(trim($c));
            }
        }

        return $out;
    }

    public function nearDuplicateName(string $a, string $b): bool
    {
        if (RecommendationSession::dishKey($a) === RecommendationSession::dishKey($b)) {
            return true;
        }
        $a = trim($a);
        $b = trim($b);
        if ($a === '' || $b === '') {
            return false;
        }
        similar_text($a, $b, $pct);
        $th = $this->strategy->float('diversity_control.similarity_threshold_percent');

        return $pct >= $th;
    }

    private function cuisineEcho(string $catalogCode, string $recent): bool
    {
        $recent = mb_strtolower(trim($recent));
        $c = mb_strtolower(trim($catalogCode));
        if ($recent === '' || $c === '') {
            return false;
        }
        if ($recent === $c) {
            return true;
        }
        if (mb_strpos($recent, $c) !== false || mb_strpos($c, $recent) !== false) {
            return true;
        }

        return false;
    }
}
