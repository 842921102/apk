<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TodayEatSelectAlternativeRequest;
use App\Models\DishRecipe;
use App\Models\RecommendationSession;
use App\Services\DishReasonGeneratorService;
use App\Services\RecommendationContextService;
use App\Services\RecommendationEventRecorder;
use App\Services\RecommendationOptimizationPipeline;
use App\Services\RecommendationRecordService;
use App\Services\RecommendationTagService;
use App\Services\UserPreferenceSignalRecorder;
use App\Support\UserDailyStatusMvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class TodayEatSelectAlternativeController extends Controller
{
    public function __construct(
        private readonly RecommendationContextService $contextService,
        private readonly RecommendationTagService $tagService,
        private readonly DishReasonGeneratorService $dishReasonGenerator,
        private readonly RecommendationRecordService $recommendationRecordService,
        private readonly RecommendationOptimizationPipeline $optimizationPipeline,
        private readonly UserPreferenceSignalRecorder $preferenceSignalRecorder,
        private readonly RecommendationEventRecorder $recommendationEventRecorder,
    ) {}

    public function __invoke(TodayEatSelectAlternativeRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $session = RecommendationSession::query()
            ->where('id', (string) $validated['recommendation_session_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $session instanceof RecommendationSession) {
            return response()->json([
                'error' => ['message' => 'session_not_found'],
            ], 404);
        }

        if ($session->status_date->toDateString() !== Carbon::today()->toDateString()) {
            return response()->json([
                'error' => ['message' => 'session_expired'],
            ], 410);
        }

        $selected = trim((string) $validated['selected_dish']);
        /** @var list<string> $excluded */
        $excluded = $session->excluded_dishes ?? [];

        if (RecommendationSession::isExcluded($selected, $excluded)) {
            return response()->json([
                'error' => ['message' => 'selected_already_used'],
            ], 422);
        }

        $last = $session->last_recommended_dish;
        if ($last !== null && RecommendationSession::dishKey($selected) === RecommendationSession::dishKey($last)) {
            return response()->json([
                'error' => ['message' => 'selected_is_current_main'],
            ], 422);
        }

        $preferences = isset($validated['preferences']) && is_array($validated['preferences'])
            ? $validated['preferences']
            : [];
        $realtimeContext = isset($validated['realtime_context']) && is_array($validated['realtime_context'])
            ? $validated['realtime_context']
            : [];

        $ctx = $this->contextService->aggregateForUser(
            $user,
            Carbon::today(),
            $preferences,
            true,
        );
        if ($realtimeContext !== []) {
            $ctx = $this->contextService->mergeMiniRealtimeContext($ctx, $realtimeContext);
        }
        $tags = $this->tagService->buildFromContext($ctx);
        $ctx['generated_tags'] = $tags;

        $normalizedPrefs = UserDailyStatusMvp::normalizedSessionPreferencesFromContext($preferences, $ctx);

        $opt = $this->optimizationPipeline->forAlternativePool(
            $user,
            $ctx,
            $normalizedPrefs,
            $selected,
            $excluded,
        );
        $ctx = array_replace_recursive($ctx, $opt['context_patch']);

        $result = $this->dishReasonGenerator->generateSelectAlternative(
            $ctx,
            $normalizedPrefs,
            $tags,
            $selected,
            $excluded,
            $last,
            $opt['generator_hints'],
        );

        $session->appendExcludedDish($result['recommended_dish']);
        $session->setLastRecommendedDish($result['recommended_dish']);

        $record = $this->recommendationRecordService->persistFromTodayEat(
            $user,
            $session,
            $ctx,
            $result,
            'alternative_selected',
        );

        $catalog = config('recommendation_dish_catalog', []);
        $catalog = is_array($catalog) ? $catalog : [];
        $this->preferenceSignalRecorder->recordAlternativeSelected($user, $selected, $last, $catalog);
        $this->recommendationEventRecorder->recommendationSelectAlternative($user, $record, $selected, $last);

        $result['recommendation_session_id'] = $session->id;
        $result['recommendation_source'] = 'alternative_selected';
        $result['recommendation_record_id'] = $record->id;
        $result['dish_recipe_id'] = DishRecipe::activeIdForRecommendedDish($result['recommended_dish']);

        return response()->json($result);
    }
}
