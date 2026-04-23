<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureDataRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 小程序拉取当前用户的运营埋点记录（原 internal 接口能力）。
 */
final class MiniappFeatureDataController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feature_type' => ['required', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'in:success,failed'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $userId = (int) $request->user()->id;

        $rows = FeatureDataRecord::query()
            ->where('user_id', $userId)
            ->where('feature_type', $validated['feature_type'])
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
}
