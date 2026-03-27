<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CirclePost;
use App\Services\CircleService;
use App\Support\LaravelAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CircleCommentController extends Controller
{
    public function __construct(
        private CircleService $circle,
    ) {}

    public function index(Request $request, CirclePost $post): JsonResponse
    {
        $viewer = LaravelAccessToken::verifyAndResolveUser($request->bearerToken());
        $visible = $this->circle->findVisibleForPublic((int) $post->id, $viewer);
        if ($visible === null) {
            abort(404, 'Not found.');
        }

        return response()->json([
            'items' => $this->circle->commentsForApi($visible),
        ]);
    }

    public function store(Request $request, CirclePost $post): JsonResponse
    {
        $user = $request->user();
        $visible = $this->circle->findVisibleForPublic((int) $post->id, $user);
        if ($visible === null) {
            abort(404, 'Not found.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $comment = $this->circle->addComment($user, $visible, $validated['content']);

        return response()->json([
            'data' => $this->circle->commentToApiArray($comment),
        ], 201);
    }
}
