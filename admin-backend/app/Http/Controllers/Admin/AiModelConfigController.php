<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAiSceneConfigRequest;
use App\Models\AiModelConfig;
use App\Services\AiModelCenterService;
use App\Support\AiScene;
use App\Support\AppRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AiModelConfigController extends Controller
{
    public function __construct(
        private AiModelCenterService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->assertCanAccessAdmin($request);

        $validated = $request->validate([
            'scene_codes' => ['nullable', 'array'],
            'scene_codes.*' => ['string', Rule::in(array_keys(AiScene::options()))],
        ]);

        $sceneCodes = isset($validated['scene_codes']) && is_array($validated['scene_codes']) && $validated['scene_codes'] !== []
            ? array_values($validated['scene_codes'])
            : array_keys(AiScene::options());

        return response()->json([
            'data' => [
                'scenes' => $this->service->listSceneConfigs($sceneCodes),
            ],
        ]);
    }

    public function options(Request $request, string $sceneCode): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $this->assertScene($sceneCode);

        return response()->json([
            'data' => $this->service->configOptionsForScene($sceneCode),
        ]);
    }

    public function save(SaveAiSceneConfigRequest $request, string $sceneCode): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $this->assertScene($sceneCode);

        $saved = $this->service->saveSceneConfig($sceneCode, $request->validated(), (int) $request->user()->id);

        return response()->json([
            'data' => $this->service->toApiArray($saved),
        ]);
    }

    public function test(Request $request, string $sceneCode): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $this->assertScene($sceneCode);

        $validated = $request->validate([
            'config_id' => ['required', 'integer', 'min:1'],
        ]);

        $config = AiModelConfig::query()
            ->where('scene_code', $sceneCode)
            ->findOrFail((int) $validated['config_id']);

        $result = $this->service->testConnection($config, (int) $request->user()->id);

        return response()->json([
            'data' => $result,
        ]);
    }

    private function assertCanAccessAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! AppRole::canAccessAdmin($user->role)) {
            abort(403, 'Forbidden.');
        }
    }

    private function assertScene(string $sceneCode): void
    {
        if (! array_key_exists($sceneCode, AiScene::options())) {
            abort(422, 'Unsupported scene_code.');
        }
    }
}

