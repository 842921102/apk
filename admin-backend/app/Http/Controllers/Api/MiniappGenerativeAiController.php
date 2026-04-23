<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MiniappGenerativeAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MiniappGenerativeAiController extends Controller
{
    public function fortuneCooking(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        try {
            return response()->json($service->fortuneCooking((int) $request->user()->id, $request->all()));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function sauceRecommend(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        try {
            return response()->json($service->sauceRecommend((int) $request->user()->id, $request->all()));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function sauceRecipe(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        try {
            return response()->json($service->sauceRecipe((int) $request->user()->id, $request->all()));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function tableMenu(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        try {
            return response()->json($service->tableMenu((int) $request->user()->id, $request->all()));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function tableDishRecipe(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        try {
            return response()->json($service->tableDishRecipe((int) $request->user()->id, $request->all()));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function recipeImage(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
            'size' => ['nullable', 'string', 'max:32'],
        ]);
        try {
            return response()->json($service->recipeImage($validated['prompt'], $validated['size'] ?? null));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['error' => ['message' => $e->getMessage()]], 502);
        }
    }

    public function ingredientsRecognize(Request $request, MiniappGenerativeAiService $service): JsonResponse
    {
        $validated = $request->validate([
            'image_base64' => ['required', 'string', 'max:12000000'],
        ]);
        try {
            return response()->json($service->ingredientsRecognize((int) $request->user()->id, $validated['image_base64']));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }
}
