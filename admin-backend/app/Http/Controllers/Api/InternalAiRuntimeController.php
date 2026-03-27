<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiModelCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InternalAiRuntimeController extends Controller
{
    public function __construct(
        private AiModelCenterService $service,
    ) {}

    public function scene(Request $request, string $sceneCode): JsonResponse
    {
        $this->assertInternalToken($request);

        return response()->json([
            'data' => $this->service->resolveRuntimeConfig($sceneCode),
        ]);
    }

    private function assertInternalToken(Request $request): void
    {
        $expected = (string) env('INTERNAL_SERVICE_TOKEN', '');
        $actual = (string) $request->header('X-Internal-Token', '');

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            Log::warning('internal_ai_runtime.unauthorized', [
                'ip' => $request->ip(),
            ]);
            abort(403, 'Forbidden.');
        }
    }
}

