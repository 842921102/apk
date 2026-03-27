<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreHistoryRequest;
use App\Models\RecipeHistory;
use App\Services\RecipeHistoryService;
use App\Support\AppRole;
use App\Support\FavoriteSourceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class HistoryController extends Controller
{
    public function __construct(
        private RecipeHistoryService $histories,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:200'],
            'source_type' => ['nullable', 'string', Rule::in(FavoriteSourceType::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $actor = $request->user();
        $userId = (int) $actor->id;

        $result = AppRole::canAccessAdmin($actor->role)
            ? $this->histories->paginateAll($validated)
            : $this->histories->paginateForUser($userId, $validated);

        return response()->json([
            'data' => $result['items'],
            'meta' => [
                'pagination' => $result['pagination'],
            ],
        ]);
    }

    public function store(StoreHistoryRequest $request): JsonResponse
    {
        $history = $this->histories->createForUser((int) $request->user()->id, $request->validated());

        return response()->json([
            'data' => $this->histories->toApiArray($history),
        ], 201);
    }

    public function show(Request $request, RecipeHistory $history): JsonResponse
    {
        $actor = $request->user();

        if ((int) $history->user_id === (int) $actor->id) {
            return response()->json([
                'data' => $this->histories->toApiArray($history),
            ]);
        }

        // 后台管理员可查看全部
        if (AppRole::canAccessAdmin($actor->role)) {
            return response()->json([
                'data' => $this->histories->toApiArray($history),
            ]);
        }

        abort(403, 'Forbidden.');
    }

    public function destroy(Request $request, RecipeHistory $history): JsonResponse
    {
        $actor = $request->user();

        $actorRole = AppRole::normalize($actor->role);
        $isOwner = (int) $history->user_id === (int) $actor->id;

        if (! $isOwner && ! in_array($actorRole, ['operator', 'super_admin'], true)) {
            abort(403, 'Forbidden.');
        }

        $history->delete();

        return response()->json([
            'data' => ['deleted' => true],
        ]);
    }
}

