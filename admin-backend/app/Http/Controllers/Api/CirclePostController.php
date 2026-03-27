<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CirclePost;
use App\Services\CircleService;
use App\Support\LaravelAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CirclePostController extends Controller
{
    public function __construct(
        private CircleService $circle,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tab' => ['nullable', 'string', 'in:recommend,latest,following,ai_generated,user_uploaded'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'keyword' => ['nullable', 'string', 'max:200'],
        ]);

        $viewer = LaravelAccessToken::verifyAndResolveUser($request->bearerToken());

        $result = $this->circle->paginateForPublic($validated, $viewer);

        return response()->json($result);
    }

    public function show(Request $request, CirclePost $post): JsonResponse
    {
        $viewer = LaravelAccessToken::verifyAndResolveUser($request->bearerToken());
        $visible = $this->circle->findVisibleForPublic((int) $post->id, $viewer);
        if ($visible === null) {
            abort(404, 'Not found.');
        }

        return response()->json([
            'data' => $this->circle->postToApiArray($visible, $viewer?->id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', 'min:1', 'max:20000'],
            'topic' => ['nullable', 'string', 'max:64'],
            'sourceType' => ['nullable', 'string', 'in:ai_generated,user_uploaded'],
            'publishSource' => ['nullable', 'string', 'in:ai_result,manual_upload'],
            'relatedProductId' => ['nullable', 'integer', 'min:1'],
            'images' => ['nullable', 'array', 'max:9'],
            'images.*' => ['string', 'max:2048'],
        ]);

        $post = $this->circle->createByUser($user, [
            'title' => (string) ($validated['title'] ?? ''),
            'description' => (string) ($validated['description'] ?? ''),
            'content' => isset($validated['content']) ? (string) $validated['content'] : null,
            'topic' => isset($validated['topic']) ? (string) $validated['topic'] : '',
            'sourceType' => isset($validated['sourceType']) ? (string) $validated['sourceType'] : null,
            'publishSource' => isset($validated['publishSource']) ? (string) $validated['publishSource'] : null,
            'relatedProductId' => $validated['relatedProductId'] ?? null,
            'images' => array_values($validated['images'] ?? []),
        ]);

        return response()->json([
            'data' => $this->circle->postToApiArray($post, (int) $user->id),
        ], 201);
    }

    public function toggleLike(Request $request, CirclePost $post): JsonResponse
    {
        $user = $request->user();
        $visible = $this->circle->findVisibleForPublic((int) $post->id, $user);
        if ($visible === null) {
            abort(404, 'Not found.');
        }

        $updated = $this->circle->toggleLike($user, $visible);

        return response()->json([
            'data' => $this->circle->postToApiArray($updated, (int) $user->id),
        ]);
    }

    public function toggleCollect(Request $request, CirclePost $post): JsonResponse
    {
        $user = $request->user();
        $visible = $this->circle->findVisibleForPublic((int) $post->id, $user);
        if ($visible === null) {
            abort(404, 'Not found.');
        }

        $updated = $this->circle->toggleCollect($user, $visible);

        return response()->json([
            'data' => $this->circle->postToApiArray($updated, (int) $user->id),
        ]);
    }

    public function myPosts(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->circle->myPosts($user);

        return response()->json(['items' => $items]);
    }
}
