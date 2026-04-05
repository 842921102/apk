<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TodayEatRecommendRequest;
use App\Models\DishRecipe;
use App\Models\RecommendationSession;
use App\Services\DishReasonGeneratorService;
use App\Services\RecommendationContextService;
use App\Services\RecommendationEventRecorder;
use App\Services\RecommendationOptimizationPipeline;
use App\Services\RecommendationRecordService;
use App\Services\RecommendationTagService;
use App\Support\UserDailyStatusMvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class TodayEatRecommendController extends Controller
{
    public function __construct(
        private readonly RecommendationContextService $contextService,
        private readonly RecommendationTagService $tagService,
        private readonly DishReasonGeneratorService $dishReasonGenerator,
        private readonly RecommendationRecordService $recommendationRecordService,
        private readonly RecommendationOptimizationPipeline $optimizationPipeline,
        private readonly RecommendationEventRecorder $recommendationEventRecorder,
    ) {}

    public function __invoke(TodayEatRecommendRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
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

        $opt = $this->optimizationPipeline->forSessionMainPick($user, $ctx, $normalizedPrefs, null, 'initial');
        $ctx = array_replace_recursive($ctx, $opt['context_patch']);

        $result = $this->dishReasonGenerator->generate($ctx, $normalizedPrefs, $tags, null, $opt['generator_hints']);

        $session = RecommendationSession::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status_date' => Carbon::today()->toDateString(),
            'excluded_dishes' => [],
            'last_pivot' => null,
        ]);
        $session->appendExcludedDish($result['recommended_dish']);
        $session->setLastRecommendedDish($result['recommended_dish']);
        $record = $this->recommendationRecordService->persistFromTodayEat(
            $user,
            $session,
            $ctx,
            $result,
            'initial',
        );
        $result['recommendation_session_id'] = $session->id;
        $result['recommendation_source'] = 'initial';
        $result['recommendation_record_id'] = $record->id;
        $result['dish_recipe_id'] = DishRecipe::activeIdForRecommendedDish($result['recommended_dish']);

        $this->recommendationEventRecorder->recommendationView($user, $record);

        return response()->json($result);
    }
}
