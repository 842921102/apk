<?php

namespace App\Services;

use App\Models\AiConnectionTestLog;
use App\Models\AiModel;
use App\Models\AiModelConfig;
use App\Models\AiProvider;
use App\Support\AiScene;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class AiModelCenterService
{
    /**
     * @param  list<string>  $sceneCodes
     * @return array<int, array<string, mixed>>
     */
    public function listSceneConfigs(array $sceneCodes): array
    {
        /** @var Collection<int, AiModelConfig> $rows */
        $rows = AiModelConfig::query()
            ->with(['provider', 'model'])
            ->whereIn('scene_code', $sceneCodes)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return $rows
            ->map(fn (AiModelConfig $row): array => $this->toApiArray($row))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveSceneConfig(string $sceneCode, array $payload, int $actorId): AiModelConfig
    {
        return DB::transaction(function () use ($sceneCode, $payload, $actorId): AiModelConfig {
            $model = AiModel::query()->with('provider')->findOrFail((int) $payload['model_id']);
            $provider = AiProvider::query()->findOrFail((int) $payload['provider_id']);

            if ((int) $model->provider_id !== (int) $provider->id) {
                abort(422, 'provider_id 与 model_id 不匹配。');
            }

            $id = isset($payload['id']) ? (int) $payload['id'] : 0;
            $config = $id > 0
                ? AiModelConfig::query()->where('scene_code', $sceneCode)->findOrFail($id)
                : new AiModelConfig();

            $config->fill([
                'scene_code' => $sceneCode,
                'provider_id' => $provider->id,
                'model_id' => $model->id,
                'base_url_override' => $payload['base_url_override'] ?? null,
                'temperature' => $payload['temperature'] ?? null,
                'timeout_ms' => $payload['timeout_ms'] ?? null,
                'fallback_model_codes' => $this->normalizeFallbackModelCodes($payload['fallback_model_codes'] ?? null),
                'is_enabled' => (bool) ($payload['is_enabled'] ?? true),
                'is_default' => (bool) ($payload['is_default'] ?? true),
                'remark' => $payload['remark'] ?? null,
                'updated_by' => $actorId,
            ]);

            if (! $config->exists) {
                $config->created_by = $actorId;
            }

            // 允许“保持原 key 不变”；仅当传入有值时覆盖
            if (isset($payload['api_key']) && is_string($payload['api_key']) && trim($payload['api_key']) !== '') {
                $config->api_key = trim($payload['api_key']);
            } elseif (! $config->exists) {
                abort(422, 'api_key 为必填。');
            }

            $config->save();

            if ($config->is_default) {
                AiModelConfig::query()
                    ->where('scene_code', $sceneCode)
                    ->where('id', '!=', $config->id)
                    ->update(['is_default' => false]);
            }

            return $config->fresh(['provider', 'model']);
        });
    }

    /**
     * @return array{
     *   status: string,
     *   request_url: string,
     *   request_payload: array<string,mixed>,
     *   response_payload: array<string,mixed>|null,
     *   error_message: string|null,
     *   error_detail: array<string,string>|null
     * }
     */
    public function testConnection(AiModelConfig $config, int $testerId): array
    {
        $config->loadMissing(['provider', 'model']);

        $provider = $config->provider;
        $model = $config->model;
        if (! $provider || ! $model) {
            abort(422, '配置缺少 provider 或 model。');
        }

        $baseUrl = rtrim((string) ($config->base_url_override ?: $provider->base_url), '/');
        $apiPath = (string) ($model->api_path ?? '');
        $requestUrl = $baseUrl.($apiPath !== '' ? '/'.ltrim($apiPath, '/') : '');

        $payload = [
            'model' => $model->model_code,
        ];

        if ($model->model_type === 'image') {
            $payload['prompt'] = '连接测试：请返回一张简单菜品插画';
            $payload['size'] = '1024x1024';
        } else {
            $payload['messages'] = [
                ['role' => 'user', 'content' => '连接测试，请回复：ok'],
            ];
            if ($config->temperature !== null) {
                $payload['temperature'] = (float) $config->temperature;
            }
        }

        $timeoutSec = max(3, (int) ceil(((int) ($config->timeout_ms ?? 12000)) / 1000));
        $status = 'failed';
        $errorMessage = null;
        $responsePayload = null;
        $errorDetail = null;

        try {
            $resp = Http::timeout($timeoutSec)
                ->acceptJson()
                ->withToken((string) $config->getAttribute('api_key'))
                ->post($requestUrl, $payload);

            $responsePayload = $resp->json() ?: ['raw' => mb_substr((string) $resp->body(), 0, 1200)];
            if ($resp->successful()) {
                $status = 'success';
            } else {
                $errorMessage = 'http_'.$resp->status();
                $errorDetail = $this->extractUpstreamErrorDetail($responsePayload);
                $summary = $this->formatErrorDetailSummary($errorDetail);
                if ($summary !== '') {
                    $errorMessage .= ' '.$summary;
                }
            }
        } catch (\Throwable $e) {
            $errorMessage = mb_substr($e->getMessage(), 0, 500);
        }

        AiConnectionTestLog::query()->create([
            'scene_code' => $config->scene_code,
            'provider_id' => $provider->id,
            'model_id' => $model->id,
            'request_url' => $requestUrl,
            'request_payload' => $payload,
            'response_payload' => $responsePayload,
            'status' => $status,
            'error_message' => $errorMessage,
            'tested_by' => $testerId,
        ]);

        return [
            'status' => $status,
            'request_url' => $requestUrl,
            'request_payload' => $payload,
            'response_payload' => $responsePayload,
            'error_message' => $errorMessage,
            'error_detail' => $errorDetail,
        ];
    }

    /**
     * @param  array<string,mixed>|null  $responsePayload
     * @return array<string,string>|null
     */
    private function extractUpstreamErrorDetail(?array $responsePayload): ?array
    {
        if (! is_array($responsePayload)) {
            return null;
        }

        $raw = $responsePayload['error'] ?? $responsePayload;
        if (! is_array($raw)) {
            return null;
        }

        $out = [];
        foreach (['type', 'code', 'message', 'hint', 'detail'] as $key) {
            $v = $raw[$key] ?? null;
            if (! is_scalar($v)) {
                continue;
            }
            $s = trim((string) $v);
            if ($s !== '') {
                $out[$key] = mb_substr($s, 0, 240);
            }
        }

        return $out === [] ? null : $out;
    }

    /**
     * @param  array<string,string>|null  $errorDetail
     */
    private function formatErrorDetailSummary(?array $errorDetail): string
    {
        if (! $errorDetail) {
            return '';
        }

        $parts = [];
        foreach (['type', 'code', 'message', 'hint', 'detail'] as $key) {
            if (isset($errorDetail[$key]) && $errorDetail[$key] !== '') {
                $parts[] = $key.'='.$errorDetail[$key];
            }
        }

        return mb_substr(implode(' | ', $parts), 0, 500);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(AiModelConfig $config): array
    {
        $config->loadMissing(['provider', 'model']);
        $provider = $config->provider;
        $model = $config->model;

        return [
            'id' => $config->id,
            'scene_code' => $config->scene_code,
            'provider' => $provider ? [
                'id' => $provider->id,
                'provider_code' => $provider->provider_code,
                'provider_name' => $provider->provider_name,
                'provider_type' => $provider->provider_type,
            ] : null,
            'model' => $model ? [
                'id' => $model->id,
                'model_code' => $model->model_code,
                'model_name' => $model->model_name,
                'model_type' => $model->model_type,
                'api_path' => $model->api_path,
            ] : null,
            'url' => $config->base_url_override ?: $provider?->base_url,
            'key_masked' => $config->key_masked,
            'temperature' => $config->temperature !== null ? (float) $config->temperature : null,
            'timeout' => $config->timeout_ms,
            'fallback_model_codes' => $this->parseFallbackModelCodes($config->fallback_model_codes),
            'is_enabled' => (bool) $config->is_enabled,
            'is_default' => (bool) $config->is_default,
            'remark' => $config->remark,
            'created_at' => $config->created_at?->toIso8601String(),
            'updated_at' => $config->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function configOptionsForScene(string $sceneCode): array
    {
        $modelType = AiScene::modelTypeFor($sceneCode);

        $providers = AiProvider::query()
            ->where('is_enabled', true)
            ->orderBy('provider_name')
            ->get(['id', 'provider_code', 'provider_name', 'provider_type', 'base_url']);

        $models = AiModel::query()
            ->where('is_enabled', true)
            ->where('model_type', $modelType)
            ->orderBy('model_name')
            ->get(['id', 'provider_id', 'model_code', 'model_name', 'model_type', 'api_path']);

        return [
            'scene_code' => $sceneCode,
            'model_type' => $modelType,
            'providers' => $providers->toArray(),
            'models' => $models->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveRuntimeConfig(string $sceneCode): array
    {
        /** @var AiModelConfig|null $config */
        $config = AiModelConfig::query()
            ->with(['provider', 'model'])
            ->where('scene_code', $sceneCode)
            ->where('is_enabled', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();

        if (! $config || ! $config->provider || ! $config->model) {
            abort(404, 'scene_config_not_found');
        }

        if (! $config->provider->is_enabled || ! $config->model->is_enabled) {
            abort(422, 'scene_config_disabled');
        }

        return [
            'scene_code' => $sceneCode,
            'provider' => [
                'id' => $config->provider->id,
                'provider_code' => $config->provider->provider_code,
                'provider_name' => $config->provider->provider_name,
            ],
            'model' => [
                'id' => $config->model->id,
                'model_code' => $config->model->model_code,
                'model_name' => $config->model->model_name,
                'model_type' => $config->model->model_type,
                'api_path' => $config->model->api_path,
            ],
            'base_url' => $config->base_url_override ?: $config->provider->base_url,
            'api_key' => (string) $config->getAttribute('api_key'),
            'temperature' => $config->temperature !== null ? (float) $config->temperature : null,
            'timeout_ms' => $config->timeout_ms,
            'fallback_model_codes' => $this->parseFallbackModelCodes($config->fallback_model_codes),
        ];
    }

    /**
     * @return list<string>
     */
    private function parseFallbackModelCodes(?string $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $items = preg_split('/[\r\n,]+/', $raw) ?: [];
        $out = [];
        foreach ($items as $item) {
            $code = trim((string) $item);
            if ($code === '') {
                continue;
            }
            $out[] = $code;
            if (count($out) >= 8) {
                break;
            }
        }

        return array_values(array_unique($out));
    }

    private function normalizeFallbackModelCodes(mixed $raw): ?string
    {
        $list = [];
        if (is_string($raw)) {
            $list = $this->parseFallbackModelCodes($raw);
        } elseif (is_array($raw)) {
            $list = [];
            foreach ($raw as $item) {
                if (! is_string($item)) {
                    continue;
                }
                $code = trim($item);
                if ($code !== '') {
                    $list[] = $code;
                }
                if (count($list) >= 8) {
                    break;
                }
            }
            $list = array_values(array_unique($list));
        }

        return $list === [] ? null : implode("\n", $list);
    }
}

