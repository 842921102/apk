<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EatMemeRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InternalEatMemeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->assertInternalToken($request);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);
        $rows = EatMemeRecord::query()
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'pagination' => [
                    'page' => $rows->currentPage(),
                    'per_page' => $rows->perPage(),
                    'total' => $rows->total(),
                    'last_page' => $rows->lastPage(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertInternalToken($request);

        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'max:32'],
            'taste' => ['nullable', 'string', 'max:128'],
            'avoid' => ['nullable', 'string', 'max:128'],
            'people' => ['nullable', 'integer', 'min:1', 'max:99'],
            'result_title' => ['nullable', 'string', 'max:255'],
            'result_cuisine' => ['nullable', 'string', 'max:128'],
            'result_ingredients' => ['nullable', 'array'],
            'result_content' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:success,failed'],
            'error_message' => ['nullable', 'string', 'max:2000'],
            'requested_at' => ['nullable', 'date'],
        ]);

        $record = EatMemeRecord::query()->create([
            ...$validated,
            'channel' => (string) ($validated['channel'] ?? 'android_app'),
            'source_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
        ]);

        return response()->json(['data' => $record], 201);
    }

    public function destroy(Request $request, EatMemeRecord $eatMeme): JsonResponse
    {
        $this->assertInternalToken($request);
        $eatMeme->delete();

        return response()->json([
            'data' => ['deleted' => true],
        ]);
    }

    private function assertInternalToken(Request $request): void
    {
        $expected = (string) env('INTERNAL_SERVICE_TOKEN', '');
        $actual = (string) $request->header('X-Internal-Token', '');

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            Log::warning('internal_eat_meme.unauthorized', ['ip' => $request->ip()]);
            abort(403, 'Forbidden.');
        }
    }
}

