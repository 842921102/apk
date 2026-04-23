<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MiniappWeatherAmbientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MiniappPublicController extends Controller
{
    public function config(): JsonResponse
    {
        return response()->json([]);
    }

    public function homeBannerAmbient(Request $request, MiniappWeatherAmbientService $service): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $ambient = $service->resolve(
            isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        );

        return response()->json($ambient);
    }
}
