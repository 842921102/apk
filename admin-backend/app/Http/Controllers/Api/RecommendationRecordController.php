<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRecommendationFeedbackRequest;
use App\Models\DishRecipe;
use App\Models\Favorite;
use App\Models\RecommendationFeedbackRecord;
use App\Models\RecommendationRecord;
use App\Services\DishRecipeDetailAssembler;
use App\Services\FavoriteService;
use App\Services\RecommendationEventRecorder;
use App\Services\UserPreferenceSignalRecorder;
use App\Support\FavoriteSourceType;
use App\Support\RecommendationFeedbackTarget;
use App\Support\RecommendationFeedbackType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class RecommendationRecordController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
        private readonly DishRecipeDetailAssembler $dishRecipeDetailAssembler,
        private readonly UserPreferenceSignalRecorder $preferenceSignalRecorder,
        private readonly RecommendationEventRecorder $recommendationEventRecorder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $v = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'keyword' => ['nullable', 'string', 'max:80'],
        ]);

        $perPage = min(50, max(1, (int) ($v['per_page'] ?? 20)));
        $page = max(1, (int) ($v['page'] ?? 1));

        $keyword = isset($v['keyword']) ? trim((string) $v['keyword']) : '';
        $paginator = RecommendationRecord::query()
            ->where('user_id', $request->user()->id)
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sq) use ($keyword) {
                    $sq->where('recommended_dish', 'like', '%'.$keyword.'%')
                        ->orWhere('reason_text', 'like', '%'.$keyword.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(fn (RecommendationRecord $r) => $this->toListItem($r))->values()->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, RecommendationRecord $recommendationRecord): JsonResponse
    {
        $this->authorizeOwn($request, $recommendationRecord);

        return response()->json([
            'data' => $this->toDetail($recommendationRecord),
        ]);
    }

    /**
     * 收藏 / 取消收藏本条推荐记录（同步 favorites 表并写快照到 extra_payload）。
     */
    public function favorite(Request $request, RecommendationRecord $recommendationRecord): JsonResponse
    {
        $this->authorizeOwn($request, $recommendationRecord);

        $v = $request->validate([
            'favorited' => ['required', 'boolean'],
        ]);
        $want = $v['favorited'];

        if ($want) {
            $this->favoriteService->createForUser((int) $request->user()->id, [
                'source_type' => FavoriteSourceType::RecommendationRecord->value,
                'source_id' => (string) $recommendationRecord->id,
                'title' => $recommendationRecord->recommended_dish,
                'cuisine' => $recommendationRecord->cuisine,
                'ingredients' => $recommendationRecord->ingredients ?? [],
                'recipe_content' => $recommendationRecord->recipe_content,
                'extra_payload' => $this->favoriteExtraFromRecord($recommendationRecord),
            ]);
            $recommendationRecord->update(['is_favorited' => true]);
            $this->preferenceSignalRecorder->recordRecommendationRecordFavorited($request->user(), $recommendationRecord);
        } else {
            Favorite::query()
                ->where('user_id', $request->user()->id)
                ->where('source_type', FavoriteSourceType::RecommendationRecord->value)
                ->where('source_id', (string) $recommendationRecord->id)
                ->delete();
            $recommendationRecord->update(['is_favorited' => false]);
        }

        return response()->json([
            'data' => [
                'is_favorited' => $want,
            ],
        ]);
    }

    public function feedback(
        StoreRecommendationFeedbackRequest $request,
        RecommendationRecord $recommendationRecord,
    ): JsonResponse {
        $this->authorizeOwn($request, $recommendationRecord);

        /** @var array{feedback_type: string, feedback_reason?: string|null, feedback_target?: string|null} $v */
        $v = $request->validated();
        $type = RecommendationFeedbackType::from($v['feedback_type']);
        $reason = isset($v['feedback_reason']) && $v['feedback_reason'] !== '' ? $v['feedback_reason'] : null;
        $targetRaw = $v['feedback_target'] ?? null;

        $target = $targetRaw !== null && $targetRaw !== ''
            ? RecommendationFeedbackTarget::from($targetRaw)
            : ($type === RecommendationFeedbackType::ReasonAccurate
                ? RecommendationFeedbackTarget::ReasonText
                : RecommendationFeedbackTarget::Result);

        RecommendationFeedbackRecord::query()->create([
            'user_id' => (int) $request->user()->id,
            'recommendation_record_id' => $recommendationRecord->id,
            'feedback_type' => $type->value,
            'feedback_reason' => $reason,
            'feedback_target' => $target->value,
        ]);

        $this->preferenceSignalRecorder->recordFeedback(
            $request->user(),
            $recommendationRecord,
            $type,
            $reason,
            Carbon::today(),
        );

        $this->recommendationEventRecorder->feedback($request->user(), $recommendationRecord, $type, $reason);

        return response()->json([
            'data' => ['ok' => true],
        ]);
    }

    private function authorizeOwn(Request $request, RecommendationRecord $recommendationRecord): void
    {
        if ((int) $recommendationRecord->user_id !== (int) $request->user()->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(RecommendationRecord $r): array
    {
        $tags = is_array($r->tags) ? $r->tags : [];
        $reason = (string) $r->reason_text;
        $summary = mb_strlen($reason) > 72 ? mb_substr($reason, 0, 72).'…' : $reason;

        return [
            'id' => $r->id,
            'recommended_dish' => $r->recommended_dish,
            'tags' => array_slice($tags, 0, 4),
            'reason_summary' => $summary,
            'recommendation_date' => $r->recommendation_date->format('Y-m-d'),
            'recommendation_source' => $r->recommendation_source,
            'is_favorited' => (bool) $r->is_favorited,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toDetail(RecommendationRecord $r): array
    {
        return [
            'id' => $r->id,
            'session_id' => $r->session_id,
            'recommendation_source' => $r->recommendation_source,
            'recommendation_date' => $r->recommendation_date->format('Y-m-d'),
            'meal_type' => $r->meal_type,
            'recommended_dish' => $r->recommended_dish,
            'tags' => $r->tags ?? [],
            'reason_text' => $r->reason_text,
            'destiny_text' => $r->destiny_text,
            'destiny_style' => $r->destiny_style,
            'reason_style' => $r->reason_style,
            'alternatives' => $r->alternatives ?? [],
            'cuisine' => $r->cuisine,
            'ingredients' => $r->ingredients ?? [],
            'recipe_content' => $r->recipe_content,
            'dish_recipe_id' => DishRecipe::activeIdForRecommendedDish($r->recommended_dish),
            'recipe_detail' => $this->dishRecipeDetailAssembler->forRecord($r),
            'weather_snapshot' => $r->weather_snapshot,
            'festival_snapshot' => $r->festival_snapshot,
            'user_profile_snapshot' => $r->user_profile_snapshot,
            'daily_status_snapshot' => $r->daily_status_snapshot,
            'is_favorited' => (bool) $r->is_favorited,
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function favoriteExtraFromRecord(RecommendationRecord $r): array
    {
        return [
            'recommendation_record_id' => $r->id,
            'reason_text' => $r->reason_text,
            'destiny_text' => $r->destiny_text,
            'destiny_style' => $r->destiny_style,
            'reason_style' => $r->reason_style,
            'tags' => $r->tags ?? [],
            'alternatives' => $r->alternatives ?? [],
            'recommendation_source' => $r->recommendation_source,
            'recommendation_date' => $r->recommendation_date->format('Y-m-d'),
            'session_id' => $r->session_id,
            'weather_snapshot' => $r->weather_snapshot,
            'festival_snapshot' => $r->festival_snapshot,
            'user_profile_snapshot' => $r->user_profile_snapshot,
            'daily_status_snapshot' => $r->daily_status_snapshot,
            'cuisine' => $r->cuisine,
            'ingredients' => $r->ingredients ?? [],
        ];
    }
}
