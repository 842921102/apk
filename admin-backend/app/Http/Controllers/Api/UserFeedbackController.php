<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserFeedbackRequest;
use App\Models\UserFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $items = UserFeedback::query()
            ->where('user_id', (int) $request->user()->id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    public function store(StoreUserFeedbackRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $feedback = UserFeedback::query()->create([
            'user_id' => (int) $request->user()->id,
            'title' => trim((string) $payload['title']),
            'content' => trim((string) $payload['content']),
            'contact' => isset($payload['contact']) ? trim((string) $payload['contact']) : null,
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $feedback,
        ], 201);
    }
}
