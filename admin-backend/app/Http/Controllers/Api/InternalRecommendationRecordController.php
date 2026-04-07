<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EatMemeRecord;
use App\Models\FeatureDataRecord;
use App\Models\RecommendationRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class InternalRecommendationRecordController extends Controller
{
    public function latest(Request $request): JsonResponse
    {
        $this->assertInternalToken($request);

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $limit = (int) ($validated['limit'] ?? 5);
        $rows = RecommendationRecord::query()
            ->when(
                isset($validated['user_id']),
                fn ($q) => $q->where('user_id', (int) $validated['user_id'])
            )
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $data = $rows->map(function (RecommendationRecord $r): array {
            $weather = is_array($r->weather_snapshot) ? $r->weather_snapshot : [];
            $festival = is_array($r->festival_snapshot) ? $r->festival_snapshot : [];
            $profile = is_array($r->user_profile_snapshot) ? $r->user_profile_snapshot : [];
            $daily = is_array($r->daily_status_snapshot) ? $r->daily_status_snapshot : [];
            $feature = $this->findNearbyFeatureRecord($r);
            $eatMeme = $this->findNearbyEatMemeRecord($r);
            $missing = [];
            if (trim((string) $r->reason_text) === '') {
                $missing[] = 'reason_text';
            }
            if (trim((string) ($r->destiny_text ?? '')) === '') {
                $missing[] = 'destiny_text';
            }
            if ($weather === []) {
                $missing[] = 'weather_snapshot';
            }
            if ($festival === []) {
                $missing[] = 'festival_snapshot';
            }
            if ($profile === []) {
                $missing[] = 'user_profile_snapshot';
            }
            if ($daily === []) {
                $missing[] = 'daily_status_snapshot';
            }
            // 加权闭环评分：文案/快照是核心，埋点链路次之。
            $weights = [
                'reason_text' => 24,
                'destiny_text' => 16,
                'weather_snapshot' => 18,
                'festival_snapshot' => 10,
                'user_profile_snapshot' => 14,
                'daily_status_snapshot' => 10,
                'feature_data_record' => 4,
                'eat_meme_record' => 4,
            ];
            $score = 0;
            if (! in_array('reason_text', $missing, true)) $score += $weights['reason_text'];
            if (! in_array('destiny_text', $missing, true)) $score += $weights['destiny_text'];
            if (! in_array('weather_snapshot', $missing, true)) $score += $weights['weather_snapshot'];
            if (! in_array('festival_snapshot', $missing, true)) $score += $weights['festival_snapshot'];
            if (! in_array('user_profile_snapshot', $missing, true)) $score += $weights['user_profile_snapshot'];
            if (! in_array('daily_status_snapshot', $missing, true)) $score += $weights['daily_status_snapshot'];
            if ($feature !== null) $score += $weights['feature_data_record'];
            if ($eatMeme !== null) $score += $weights['eat_meme_record'];
            if ($feature === null) {
                $missing[] = 'feature_data_record';
            }
            if ($eatMeme === null) {
                $missing[] = 'eat_meme_record';
            }

            $status = $score >= 90 ? 'complete' : ($score >= 75 ? 'mostly_complete' : 'incomplete');

            return [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'session_id' => $r->session_id,
                'created_at' => $r->created_at?->toIso8601String(),
                'recommended_dish' => $r->recommended_dish,
                'reason_style' => $r->reason_style,
                'destiny_style' => $r->destiny_style,
                'has_reason_text' => trim((string) $r->reason_text) !== '',
                'has_destiny_text' => trim((string) ($r->destiny_text ?? '')) !== '',
                'has_weather_snapshot' => $weather !== [],
                'has_festival_snapshot' => $festival !== [],
                'has_user_profile_snapshot' => $profile !== [],
                'has_daily_status_snapshot' => $daily !== [],
                'has_feature_data_record' => $feature !== null,
                'has_eat_meme_record' => $eatMeme !== null,
                'completeness_score' => $score,
                'completeness_status' => $status,
                'missing_fields' => $missing,
                'closure_comment' => $this->buildClosureComment($score, $missing),
                'weather_snapshot' => $r->weather_snapshot,
                'festival_snapshot' => $r->festival_snapshot,
                'user_profile_snapshot' => $r->user_profile_snapshot,
                'daily_status_snapshot' => $r->daily_status_snapshot,
                'feature_data_record' => $feature,
                'eat_meme_record' => $eatMeme,
                'recommendation_fallback' => (bool) $r->recommendation_fallback,
                'used_default_profile' => (bool) $r->used_default_profile,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'count' => $data->count(),
                'limit' => $limit,
            ],
        ]);
    }

    private function assertInternalToken(Request $request): void
    {
        $expected = (string) env('INTERNAL_SERVICE_TOKEN', '');
        $actual = (string) $request->header('X-Internal-Token', '');

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            Log::warning('internal_recommendation_record.unauthorized', ['ip' => $request->ip()]);
            abort(403, 'Forbidden.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findNearbyFeatureRecord(RecommendationRecord $r): ?array
    {
        $base = $r->created_at instanceof Carbon ? $r->created_at : null;
        if (! $base) {
            return null;
        }
        $row = FeatureDataRecord::query()
            ->where('feature_type', 'custom_cuisine')
            ->where('status', 'success')
            ->where('title', $r->recommended_dish)
            ->whereBetween('created_at', [$base->copy()->subMinutes(10), $base->copy()->addMinutes(10)])
            ->orderByDesc('id')
            ->first();
        if (! $row) return null;
        return [
            'id' => $row->id,
            'sub_type' => $row->sub_type,
            'created_at' => $row->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findNearbyEatMemeRecord(RecommendationRecord $r): ?array
    {
        $base = $r->created_at instanceof Carbon ? $r->created_at : null;
        if (! $base) {
            return null;
        }
        $row = EatMemeRecord::query()
            ->where('status', 'success')
            ->where('result_title', $r->recommended_dish)
            ->whereBetween('created_at', [$base->copy()->subMinutes(10), $base->copy()->addMinutes(10)])
            ->orderByDesc('id')
            ->first();
        if (! $row) return null;
        return [
            'id' => $row->id,
            'created_at' => $row->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<string>  $missing
     */
    private function buildClosureComment(int $score, array $missing): string
    {
        if ($score >= 90) {
            return '链路完整：推荐内容、上下文快照与分析埋点均已落库。';
        }
        if ($score >= 75) {
            return '链路基本完整：核心推荐已落库，但部分快照或埋点缺失。';
        }
        return '链路不完整：建议优先排查缺失字段对应的请求透传与落库逻辑。';
    }
}
