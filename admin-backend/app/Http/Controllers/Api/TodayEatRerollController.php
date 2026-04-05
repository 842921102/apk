<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TodayEatRerollRequest;
use App\Models\RecommendationSession;
use App\Services\DishReasonGeneratorService;
use App\Services\RecommendationConfigService;
use App\Services\RecommendationContextService;
use App\Services\RecommendationEventRecorder;
use App\Services\RecommendationOptimizationPipeline;
use App\Services\RecommendationRecordService;
use App\Services\RecommendationTagService;
use App\Support\UserDailyStatusMvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class TodayEatRerollController extends Controller
{
    public function __construct(
        private readonly RecommendationContextService $contextService,
        private readonly RecommendationTagService $tagService,
        private readonly DishReasonGeneratorService $dishReasonGenerator,
        private readonly RecommendationRecordService $recommendationRecordService,
        private readonly RecommendationOptimizationPipeline $optimizationPipeline,
        private readonly RecommendationEventRecorder $recommendationEventRecorder,
        private readonly RecommendationConfigService $recommendationConfig,
    ) {}

    public function __invoke(TodayEatRerollRequest $request): JsonResponse
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

        $preferences = isset($validated['preferences']) && is_array($validated['preferences'])
            ? $validated['preferences']
            : [];

        $ctx = $this->contextService->aggregateForUser(
            $user,
            Carbon::today(),
            $preferences,
            true,
        );
        $tags = $this->tagService->buildFromContext($ctx);
        $ctx['generated_tags'] = $tags;

        $normalizedPrefs = UserDailyStatusMvp::normalizedSessionPreferencesFromContext($preferences, $ctx);

        $pivotKey = $this->recommendationConfig->nextPivotKey($session->last_pivot);
        $pivot = $this->recommendationConfig->pivotSpec($pivotKey);

        $previousMain = $session->last_recommended_dish;

        /** @var list<string> $excluded */
        $excluded = $session->excluded_dishes ?? [];
        $reroll = [
            'excluded_dishes' => $excluded,
            'pivot_key' => $pivot['key'],
            'pivot_label_cn' => $pivot['label_cn'],
            'pivot_hint_cn' => $pivot['hint_cn'],
        ];

        $opt = $this->optimizationPipeline->forSessionMainPick($user, $ctx, $normalizedPrefs, $session, 'reroll');
        $ctx = array_replace_recursive($ctx, $opt['context_patch']);

        $result = $this->dishReasonGenerator->generate($ctx, $normalizedPrefs, $tags, $reroll, $opt['generator_hints']);

        $session->appendExcludedDish($result['recommended_dish']);
        $session->setLastPivot($pivotKey);
        $session->setLastRecommendedDish($result['recommended_dish']);

        $record = $this->recommendationRecordService->persistFromTodayEat(
            $user,
            $session,
            $ctx,
            $result,
            'reroll',
        );

        $result['recommendation_session_id'] = $session->id;
        $result['recommendation_source'] = 'reroll';
        $result['recommendation_record_id'] = $record->id;
        $result['dish_recipe_id'] = DishRecipe::activeIdForRecommendedDish($result['recommended_dish']);

        $this->recommendationEventRecorder->recommendationReroll($user, $record, $previousMain);

        return response()->json($result);
    }
}
