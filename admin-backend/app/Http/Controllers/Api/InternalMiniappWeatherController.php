<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MiniappWeatherAmbientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InternalMiniappWeatherController extends Controller
{
    public function ambient(Request $request, MiniappWeatherAmbientService $service): JsonResponse
    {
        $this->assertInternalToken($request);

        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $ambient = $service->resolve(
            isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            isset($validated['longitude']) ? (float) $validated['longitude'] : null
        );

        return response()->json($ambient);
    }

    private function assertInternalToken(Request $request): void
    {
        $expected = (string) env('INTERNAL_SERVICE_TOKEN', '');
        $actual = (string) $request->header('X-Internal-Token', '');

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            Log::warning('internal_miniapp_weather.unauthorized', ['ip' => $request->ip()]);
            abort(403, 'Forbidden.');
        }
    }
}

