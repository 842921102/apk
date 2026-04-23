<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecipeHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GalleryListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $rows = RecipeHistory::query()
            ->where('user_id', $userId)
            ->whereNotNull('extra_payload->image_url')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'user_id', 'title', 'cuisine', 'ingredients', 'request_payload', 'response_content', 'extra_payload', 'created_at']);

        $items = [];
        foreach ($rows as $r) {
            $url = trim((string) data_get($r->extra_payload, 'image_url'));
            if ($url === '') {
                continue;
            }
            $ingredientsRaw = $r->ingredients;
            $ingredients = is_array($ingredientsRaw)
                ? array_values(array_filter(array_map('strval', $ingredientsRaw)))
                : [];
            $promptCandidate = $r->request_payload ?? $r->response_content;
            $prompt = $promptCandidate === null
                ? null
                : (is_string($promptCandidate) ? $promptCandidate : json_encode($promptCandidate, JSON_UNESCAPED_UNICODE));

            $items[] = [
                'id' => (string) $r->id,
                'url' => $url,
                'recipeName' => trim((string) $r->title) !== '' ? (string) $r->title : '未命名',
                'recipeId' => '',
                'cuisine' => trim((string) ($r->cuisine ?? '')),
                'ingredients' => $ingredients,
                'generatedAt' => $r->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'prompt' => $prompt,
                'userId' => (string) $r->user_id,
            ];
        }

        return response()->json(['items' => $items]);
    }
}
