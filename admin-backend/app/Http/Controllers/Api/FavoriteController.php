<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckFavoriteRequest;
use App\Http\Requests\Api\StoreFavoriteRequest;
use App\Models\Favorite;
use App\Services\FavoriteService;
use App\Support\FavoriteSourceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class FavoriteController extends Controller
{
    public function __construct(
        private FavoriteService $favorites,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:200'],
            'source_type' => ['nullable', 'string', Rule::in(FavoriteSourceType::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $userId = (int) $request->user()->id;
        $result = $this->favorites->paginateForUser($userId, $validated);

        return response()->json([
            'data' => $result['items'],
            'meta' => [
                'pagination' => $result['pagination'],
            ],
        ]);
    }

    public function store(StoreFavoriteRequest $request): JsonResponse
    {
        $favorite = $this->favorites->createForUser((int) $request->user()->id, $request->validated());

        return response()->json([
            'data' => $this->favorites->toApiArray($favorite),
        ], 201);
    }

    public function show(Request $request, Favorite $favorite): JsonResponse
    {
        $this->authorizeFavoriteRead($request, $favorite);

        return response()->json([
            'data' => $this->favorites->toApiArray($favorite),
        ]);
    }

    public function destroy(Request $request, Favorite $favorite): JsonResponse
    {
        $userId = (int) $request->user()->id;
        if ((int) $favorite->user_id !== $userId) {
            abort(403, 'Forbidden.');
        }

        $favorite->delete();

        return response()->json([
            'data' => ['deleted' => true],
        ]);
    }

    public function check(CheckFavoriteRequest $request): JsonResponse
    {
        $v = $request->validated();
        $favorite = $this->favorites->isFavorited(
            (int) $request->user()->id,
            (string) $v['source_type'],
            (string) $v['source_id'],
        );

        return response()->json([
            'data' => [
                'is_favorited' => $favorite !== null,
                'id' => $favorite?->id,
            ],
        ]);
    }

    private function authorizeFavoriteRead(Request $request, Favorite $favorite): void
    {
        $user = $request->user();
        if ((int) $favorite->user_id === (int) $user->id) {
            return;
        }

        abort(403, 'Forbidden.');
    }
}
