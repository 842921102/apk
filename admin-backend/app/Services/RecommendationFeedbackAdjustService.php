<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\RecommendationFeedbackRecord;
use App\Models\RecommendationRecord;
use App\Models\User;
use App\Support\RecommendationFeedbackReason;
use App\Support\RecommendationFeedbackType;
use Illuminate\Support\Carbon;

/**
 * 将 recommendation_feedback_records（及收藏）转为可解释的打分信号，供规则引擎使用。
 */
final class RecommendationFeedbackAdjustService
{
    public function __construct(
        private readonly RecommendationConfigService $strategy,
    ) {}

    /**
     * @return array{
     *   boost_flavor_keywords: list<string>,
     *   penalize_flavor_keywords: list<string>,
     *   boost_cuisine_types: list<string>,
     *   penalize_cuisine_types: list<string>,
     *   penalize_ingredient_tokens: list<string>,
     *   penalize_dish_name_tokens: list<string>,
     *   complexity_hint: 'prefer_quick'|'prefer_medium'|'neutral',
     *   temperature_hint: 'prefer_warm'|'prefer_cold'|'prefer_light'|'neutral',
     *   explain_style_note: string|null,
     *   raw_traces: list<array<string, mixed>>
     * }
     */
    public function buildSignals(User $user, Carbon $today): array
    {
        $lookback = $this->strategy->int('feedback_signal_builder.lookback_days');
        $since = $today->copy()->subDays($lookback);

        $fetchLimit = $this->strategy->int('feedback_signal_builder.fetch_limit');
        $rows = RecommendationFeedbackRecord::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->orderByDesc('id')
            ->limit($fetchLimit)
            ->get();

        $recordIds = $rows->pluck('recommendation_record_id')->unique()->filter()->values()->all();
        /** @var array<int, RecommendationRecord> $records */
        $records = RecommendationRecord::query()
            ->whereIn('id', $recordIds)
            ->get()
            ->keyBy('id');

        $boostFlavor = [];
        $penalizeFlavor = [];
        $boostCuisine = [];
        $penalizeCuisine = [];
        $penalizeIng = [];
        $penalizeDishTokens = [];
        $complexityHint = 'neutral';
        $temperatureHint = 'neutral';
        $explainStyleNote = null;
        $traces = [];

        foreach ($rows as $fb) {
            $rec = $records[$fb->recommendation_record_id] ?? null;
            $reason = $fb->feedback_reason;

            match ($fb->feedback_type) {
                RecommendationFeedbackType::WantToEat->value => $this->applyWantToEat($rec, $boostFlavor, $boostCuisine, $traces, $fb->id),
                RecommendationFeedbackType::NotToday->value => $this->applyNotToday($rec, $today, $penalizeFlavor, $penalizeCuisine, $penalizeDishTokens, $traces, $fb->id, $reason),
                RecommendationFeedbackType::NotSuitable->value => $this->applyNotSuitable($rec, $penalizeFlavor, $penalizeCuisine, $penalizeIng, $complexityHint, $temperatureHint, $traces, $fb->id, $reason),
                RecommendationFeedbackType::ReasonAccurate->value => $this->applyReasonAccurate($explainStyleNote, $traces, $fb->id),
                RecommendationFeedbackType::ChangeDirection->value => null,
                default => null,
            };
        }

        $this->mergeFavoriteBoosts($user, $boostFlavor, $boostCuisine, $traces);

        return [
            'boost_flavor_keywords' => array_values(array_unique(array_filter($boostFlavor))),
            'penalize_flavor_keywords' => array_values(array_unique(array_filter($penalizeFlavor))),
            'boost_cuisine_types' => array_values(array_unique(array_filter($boostCuisine))),
            'penalize_cuisine_types' => array_values(array_unique(array_filter($penalizeCuisine))),
            'penalize_ingredient_tokens' => array_values(array_unique(array_filter($penalizeIng))),
            'penalize_dish_name_tokens' => array_values(array_unique(array_filter($penalizeDishTokens))),
            'complexity_hint' => $complexityHint,
            'temperature_hint' => $temperatureHint,
            'explain_style_note' => $explainStyleNote,
            'raw_traces' => $traces,
        ];
    }

    /**
     * @param  list<string>  $boostFlavor
     * @param  list<string>  $boostCuisine
     * @param  list<array<string, mixed>>  $traces
     */
    private function applyWantToEat(?RecommendationRecord $rec, array &$boostFlavor, array &$boostCuisine, array &$traces, int $feedbackId): void
    {
        if (! $rec instanceof RecommendationRecord) {
            return;
        }
        foreach ($rec->tags ?? [] as $t) {
            $s = $this->norm((string) $t);
            if ($s !== '') {
                $boostFlavor[] = $s;
            }
        }
        if ($rec->cuisine !== null && trim((string) $rec->cuisine) !== '') {
            $boostCuisine[] = $this->norm((string) $rec->cuisine);
        }
        $slice = $this->strategy->int('feedback_signal_builder.want_ingredient_slice');
        foreach (array_slice($rec->ingredients ?? [], 0, $slice) as $ing) {
            $s = $this->norm((string) $ing);
            if (mb_strlen($s) >= 2) {
                $boostFlavor[] = $s;
            }
        }
        $traces[] = ['feedback_id' => $feedbackId, 'rule' => 'want_to_eat_boost', 'dish' => $rec->recommended_dish];
    }

    /**
     * @param  list<string>  $penalizeFlavor
     * @param  list<string>  $penalizeCuisine
     * @param  list<string>  $penalizeDishTokens
     * @param  list<array<string, mixed>>  $traces
     */
    private function applyNotToday(
        ?RecommendationRecord $rec,
        Carbon $today,
        array &$penalizeFlavor,
        array &$penalizeCuisine,
        array &$penalizeDishTokens,
        array &$traces,
        int $feedbackId,
        ?string $reason,
    ): void {
        if (! $rec instanceof RecommendationRecord) {
            return;
        }
        if ($rec->recommendation_date->toDateString() !== $today->toDateString()) {
            return;
        }
        foreach ($rec->tags ?? [] as $t) {
            $s = $this->norm((string) $t);
            if ($s !== '') {
                $penalizeFlavor[] = $s;
            }
        }
        if ($rec->cuisine !== null && trim((string) $rec->cuisine) !== '') {
            $penalizeCuisine[] = $this->norm((string) $rec->cuisine);
        }
        $penalizeDishTokens[] = $this->norm($rec->recommended_dish);
        $traces[] = ['feedback_id' => $feedbackId, 'rule' => 'not_today_penalty', 'dish' => $rec->recommended_dish, 'reason' => $reason];
    }

    /**
     * @param  list<string>  $penalizeFlavor
     * @param  list<string>  $penalizeCuisine
     * @param  list<string>  $penalizeIng
     * @param  list<array<string, mixed>>  $traces
     */
    private function applyNotSuitable(
        ?RecommendationRecord $rec,
        array &$penalizeFlavor,
        array &$penalizeCuisine,
        array &$penalizeIng,
        string &$complexityHint,
        string &$temperatureHint,
        array &$traces,
        int $feedbackId,
        ?string $reason,
    ): void {
        $r = RecommendationFeedbackReason::tryFrom((string) $reason) ?? null;
        if ($r === null) {
            return;
        }

        match ($r) {
            RecommendationFeedbackReason::TooGreasy => $penalizeFlavor = array_merge($penalizeFlavor, ['油腻', '油炸', '重油', '红烧', '炸']),
            RecommendationFeedbackReason::TooComplex => $complexityHint = 'prefer_quick',
            RecommendationFeedbackReason::WrongFlavor => $this->penalizeFromRecordFlavor($rec, $penalizeFlavor, $penalizeCuisine),
            RecommendationFeedbackReason::AlreadyAteRecently => $this->penalizeFromRecordAll($rec, $penalizeFlavor, $penalizeCuisine, $penalizeIng),
            RecommendationFeedbackReason::NotFitGoal => $penalizeFlavor = array_merge($penalizeFlavor, ['高糖', '油炸', '肥腻']),
            RecommendationFeedbackReason::NotFitTodayStatus => $temperatureHint = 'prefer_warm',
        };

        $traces[] = ['feedback_id' => $feedbackId, 'rule' => 'not_suitable', 'reason' => $r->value];
    }

    /**
     * @param  list<string>  $penalizeFlavor
     * @param  list<string>  $penalizeCuisine
     */
    private function penalizeFromRecordFlavor(?RecommendationRecord $rec, array &$penalizeFlavor, array &$penalizeCuisine): void
    {
        if (! $rec instanceof RecommendationRecord) {
            return;
        }
        foreach ($rec->tags ?? [] as $t) {
            $s = $this->norm((string) $t);
            if ($s !== '') {
                $penalizeFlavor[] = $s;
            }
        }
        if ($rec->cuisine) {
            $penalizeCuisine[] = $this->norm((string) $rec->cuisine);
        }
    }

    /**
     * @param  list<string>  $penalizeFlavor
     * @param  list<string>  $penalizeCuisine
     * @param  list<string>  $penalizeIng
     */
    private function penalizeFromRecordAll(?RecommendationRecord $rec, array &$penalizeFlavor, array &$penalizeCuisine, array &$penalizeIng): void
    {
        $this->penalizeFromRecordFlavor($rec, $penalizeFlavor, $penalizeCuisine);
        if (! $rec instanceof RecommendationRecord) {
            return;
        }
        foreach ($rec->ingredients ?? [] as $ing) {
            $s = $this->norm((string) $ing);
            if (mb_strlen($s) >= 2) {
                $penalizeIng[] = $s;
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $traces
     */
    private function applyReasonAccurate(?string &$explainStyleNote, array &$traces, int $feedbackId): void
    {
        $explainStyleNote = '用户曾反馈「理由挺准」：请继续保持有理有据的解释风格，可引用画像/天气/节日/状态等具体线索，避免空洞套话。';
        $traces[] = ['feedback_id' => $feedbackId, 'rule' => 'reason_accurate_style'];
    }

    /**
     * @param  list<string>  $_boostFlavor
     * @param  list<string>  $boostCuisine
     * @param  list<array<string, mixed>>  $traces
     */
    private function mergeFavoriteBoosts(User $user, array &$_boostFlavor, array &$boostCuisine, array &$traces): void
    {
        $favs = Favorite::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(24)
            ->get(['title', 'cuisine', 'ingredients', 'source_type']);

        foreach ($favs as $f) {
            if ($f->cuisine !== null && trim((string) $f->cuisine) !== '') {
                $boostCuisine[] = $this->norm((string) $f->cuisine);
            }
            foreach (array_slice($f->ingredients ?? [], 0, 4) as $ing) {
                $_boostFlavor[] = $this->norm((string) $ing);
            }
        }
        if ($favs->isNotEmpty()) {
            $traces[] = ['rule' => 'favorites_boost', 'count' => $favs->count()];
        }
    }

    private function norm(string $s): string
    {
        return mb_strtolower(trim($s));
    }
}
