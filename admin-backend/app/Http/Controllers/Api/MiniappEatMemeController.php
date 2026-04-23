<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EatMemeRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 小程序「吃饭梗」列表（原 internal 接口能力）。
 */
final class MiniappEatMemeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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

    public function destroy(EatMemeRecord $eatMeme): JsonResponse
    {
        $eatMeme->delete();

        return response()->json([
            'data' => ['deleted' => true],
        ]);
    }
}
