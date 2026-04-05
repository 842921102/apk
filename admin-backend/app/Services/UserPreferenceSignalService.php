<?php

namespace App\Services;

use App\Models\RecommendationSession;
use App\Models\User;
use App\Models\UserPreferenceSignal;
use App\Support\PreferenceSignalType;
use Illuminate\Support\Carbon;

/**
 * 持久化用户行为偏好信号，并在打分时提供带时间衰减的有效权重。
 */
final class UserPreferenceSignalService
{
    public function __construct(
        private readonly RecommendationConfigService $strategy,
    ) {}

    public function bump(
        int $userId,
        string $signalType,
        string $signalKey,
        ?string $signalValue,
        float $delta,
        string $source,
    ): void {
        if (! in_array($signalType, PreferenceSignalType::values(), true)) {
            return;
        }
        $key = mb_strtolower(trim($signalKey));
        if ($key === '' || abs($delta) < 0.0001) {
            return;
        }

        $row = UserPreferenceSignal::query()->firstOrNew([
            'user_id' => $userId,
            'signal_type' => $signalType,
            'signal_key' => $key,
        ]);

        $prev = (float) ($row->weight_score ?? 0);
        $wMin = $this->strategy->float('user_preference_signal_decay.weight_min');
        $wMax = $this->strategy->float('user_preference_signal_decay.weight_max');
        $next = max($wMin, min($wMax, $prev + $delta));

        $row->user_id = $userId;
        $row->signal_type = $signalType;
        $row->signal_key = $key;
        if ($signalValue !== null && trim($signalValue) !== '') {
            $row->signal_value = mb_substr(trim($signalValue), 0, 255);
        }
        $row->weight_score = $next;
        $row->source = mb_substr($source, 0, 32);
        $row->last_triggered_at = now();
        $row->save();
    }

    /**
     * 供 RecommendationScoringService 叠加：已按天衰减的有效权重（可正可负）。
     *
     * @return array{
     *   flavor: array<string, float>,
     *   cuisine: array<string, float>,
     *   health_tag: array<string, float>,
     *   mood_tag: array<string, float>,
     *   scene: array<string, float>,
     *   cooking_complexity: array<string, float>,
     *   dish: array<string, float>,
     *   row_count: int
     * }
     */
    public function buildEffectiveWeights(User $user, Carbon $now): array
    {
        $out = [
            PreferenceSignalType::Flavor => [],
            PreferenceSignalType::Cuisine => [],
            PreferenceSignalType::HealthTag => [],
            PreferenceSignalType::MoodTag => [],
            PreferenceSignalType::Scene => [],
            PreferenceSignalType::CookingComplexity => [],
            PreferenceSignalType::Dish => [],
            'row_count' => 0,
        ];

        $fetchLimit = $this->strategy->int('user_preference_signal_decay.fetch_limit');
        $rows = UserPreferenceSignal::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_triggered_at')
            ->limit($fetchLimit)
            ->get();

        $decayCap = $this->strategy->float('user_preference_signal_decay.decay_cap_days');
        $decayFloor = $this->strategy->float('user_preference_signal_decay.decay_floor');
        $effFloor = $this->strategy->float('user_preference_signal_decay.effective_abs_floor');

        foreach ($rows as $r) {
            $type = (string) $r->signal_type;
            if (! isset($out[$type]) || ! is_array($out[$type])) {
                continue;
            }
            $base = (float) $r->weight_score;
            if (abs($base) < 0.01) {
                continue;
            }
            $ts = $r->last_triggered_at;
            $days = $ts instanceof Carbon ? max(0, $now->diffInDays($ts)) : 90;
            $decay = max($decayFloor, 1.0 - min($decayCap, (float) $days) / $decayCap);
            $eff = $base * $decay;
            if (abs($eff) < $effFloor) {
                continue;
            }
            $k = (string) $r->signal_key;
            $out[$type][$k] = ($out[$type][$k] ?? 0.0) + $eff;
            $out['row_count']++;
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $catalog
     * @return array<string, mixed>|null
     */
    public static function findCatalogDishByName(array $catalog, string $name): ?array
    {
        $want = RecommendationSession::dishKey($name);
        foreach ($catalog as $dish) {
            if (! is_array($dish) || empty($dish['name'])) {
                continue;
            }
            if (RecommendationSession::dishKey((string) $dish['name']) === $want) {
                return $dish;
            }
        }

        return null;
    }
}
