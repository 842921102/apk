<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DishRecipe;
use App\Models\RecommendationRecord;
use App\Services\DishRecipeDetailAssembler;
use App\Services\RecommendationEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DishRecipeController extends Controller
{
    public function __construct(
        private readonly DishRecipeDetailAssembler $dishRecipeDetailAssembler,
        private readonly RecommendationEventRecorder $recommendationEventRecorder,
    ) {}

    public function show(Request $request, DishRecipe $dishRecipe): JsonResponse
    {
        if (! $dishRecipe->is_active) {
            abort(404);
        }

        $user = $request->user();
        $fromRecord = null;
        $rid = $request->query('recommendation_record_id');
        if ($rid !== null && $rid !== '' && is_numeric($rid)) {
            $fromRecord = RecommendationRecord::query()
                ->where('user_id', $user->id)
                ->where('id', (int) $rid)
                ->first();
        }
        $this->recommendationEventRecorder->recipeView($user, $dishRecipe, $fromRecord);

        return response()->json([
            'data' => [
                'dish_recipe_id' => $dishRecipe->id,
                'recipe_detail' => $this->dishRecipeDetailAssembler->standalone($dishRecipe),
            ],
        ]);
    }
}
