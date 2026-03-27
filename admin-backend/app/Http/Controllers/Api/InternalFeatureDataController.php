<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureDataRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InternalFeatureDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->assertInternalToken($request);

        $validated = $request->validate([
            'feature_type' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'in:success,failed'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $rows = FeatureDataRecord::query()
            ->when(! empty($validated['feature_type']), fn ($q) => $q->where('feature_type', $validated['feature_type']))
            ->when(! empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
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
            'feature_type' => ['required', 'string', 'in:table_menu,fortune_cooking,sauce_design,gallery,custom_cuisine'],
            'channel' => ['nullable', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:success,failed'],
            'title' => ['nullable', 'string', 'max:255'],
            'sub_type' => ['nullable', 'string', 'max:64'],
            'input_payload' => ['nullable', 'array'],
            'result_payload' => ['nullable', 'array'],
            'result_summary' => ['nullable', 'string'],
            'error_message' => ['nullable', 'string', 'max:3000'],
            'requested_at' => ['nullable', 'date'],
        ]);

        $record = FeatureDataRecord::query()->create([
            ...$validated,
            'channel' => (string) ($validated['channel'] ?? 'mini_program'),
            'source_ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
        ]);

        return response()->json(['data' => $record], 201);
    }

    private function assertInternalToken(Request $request): void
    {
        $expected = (string) env('INTERNAL_SERVICE_TOKEN', '');
        $actual = (string) $request->header('X-Internal-Token', '');

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            Log::warning('internal_feature_data.unauthorized', ['ip' => $request->ip()]);
            abort(403, 'Forbidden.');
        }
    }
}

