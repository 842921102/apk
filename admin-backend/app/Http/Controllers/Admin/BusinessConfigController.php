<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TencentCosService;
use App\Support\AppRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BusinessConfigController extends Controller
{
    public function __construct(
        private TencentCosService $cosService,
    ) {}

    public function getTencentCos(Request $request): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $config = $this->cosService->getConfig();
        $config['secret_key'] = $this->maskSecret((string) ($config['secret_key'] ?? ''));

        return response()->json(['data' => $config]);
    }

    public function updateTencentCos(Request $request): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $validated = $this->normalizeTencentCosPayload($request->validate([
            'secret_id' => ['nullable', 'string', 'max:200'],
            'secretId' => ['nullable', 'string', 'max:200'],
            'secret_key' => ['nullable', 'string', 'max:200'],
            'secretKey' => ['nullable', 'string', 'max:200'],
            'bucket' => ['nullable', 'string', 'max:200'],
            'region' => ['nullable', 'string', 'max:120'],
            'domain' => ['nullable', 'string', 'max:255'],
            'upload_prefix' => ['nullable', 'string', 'max:255'],
            'uploadPrefix' => ['nullable', 'string', 'max:255'],
            'use_https' => ['nullable', 'boolean'],
            'useHttps' => ['nullable', 'boolean'],
            'use_unique_name' => ['nullable', 'boolean'],
            'useUniqueName' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'in:public,private'],
            'allowed_extensions' => ['nullable', 'string', 'max:500'],
            'allowedExtensions' => ['nullable', 'string', 'max:500'],
            'max_file_size' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'maxFileSize' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'overwrite_enabled' => ['nullable', 'boolean'],
            'overwriteEnabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'isDefault' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
            'isEnabled' => ['nullable', 'boolean'],
            'remark' => ['nullable', 'string', 'max:2000'],
        ]));

        $saved = $this->cosService->saveConfig($validated);
        $config = $this->cosService->getConfig();
        $config['secret_key'] = $this->maskSecret((string) ($config['secret_key'] ?? ''));

        return response()->json([
            'message' => '保存成功',
            'data' => [
                'id' => $saved->id,
                'config' => $config,
            ],
        ]);
    }

    public function testTencentCosConnection(Request $request): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $payload = $this->normalizeTencentCosPayload($request->validate([
            'secret_id' => ['nullable', 'string', 'max:200'],
            'secretId' => ['nullable', 'string', 'max:200'],
            'secret_key' => ['nullable', 'string', 'max:200'],
            'secretKey' => ['nullable', 'string', 'max:200'],
            'bucket' => ['nullable', 'string', 'max:200'],
            'region' => ['nullable', 'string', 'max:120'],
            'domain' => ['nullable', 'string', 'max:255'],
            'upload_prefix' => ['nullable', 'string', 'max:255'],
            'uploadPrefix' => ['nullable', 'string', 'max:255'],
            'use_https' => ['nullable', 'boolean'],
            'useHttps' => ['nullable', 'boolean'],
            'use_unique_name' => ['nullable', 'boolean'],
            'useUniqueName' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'in:public,private'],
            'allowed_extensions' => ['nullable', 'string', 'max:500'],
            'allowedExtensions' => ['nullable', 'string', 'max:500'],
            'max_file_size' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'maxFileSize' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'overwrite_enabled' => ['nullable', 'boolean'],
            'overwriteEnabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'isDefault' => ['nullable', 'boolean'],
        ]));

        $result = $this->cosService->testConnection($payload);

        return response()->json(['data' => $result], $result['ok'] ? 200 : 422);
    }

    public function testTencentCosUpload(Request $request): JsonResponse
    {
        $this->assertCanAccessAdmin($request);
        $payload = $this->normalizeTencentCosPayload($request->validate([
            'secret_id' => ['nullable', 'string', 'max:200'],
            'secretId' => ['nullable', 'string', 'max:200'],
            'secret_key' => ['nullable', 'string', 'max:200'],
            'secretKey' => ['nullable', 'string', 'max:200'],
            'bucket' => ['nullable', 'string', 'max:200'],
            'region' => ['nullable', 'string', 'max:120'],
            'domain' => ['nullable', 'string', 'max:255'],
            'upload_prefix' => ['nullable', 'string', 'max:255'],
            'uploadPrefix' => ['nullable', 'string', 'max:255'],
            'use_https' => ['nullable', 'boolean'],
            'useHttps' => ['nullable', 'boolean'],
            'use_unique_name' => ['nullable', 'boolean'],
            'useUniqueName' => ['nullable', 'boolean'],
            'visibility' => ['nullable', 'in:public,private'],
            'allowed_extensions' => ['nullable', 'string', 'max:500'],
            'allowedExtensions' => ['nullable', 'string', 'max:500'],
            'max_file_size' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'maxFileSize' => ['nullable', 'integer', 'min:1', 'max:2048'],
            'overwrite_enabled' => ['nullable', 'boolean'],
            'overwriteEnabled' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'isDefault' => ['nullable', 'boolean'],
        ]));

        $result = $this->cosService->testUpload($payload);

        return response()->json(['data' => $result], $result['ok'] ? 200 : 422);
    }

    private function assertCanAccessAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! AppRole::canAccessAdmin($user->role)) {
            abort(403, 'Forbidden.');
        }
    }

    private function maskSecret(string $secret): string
    {
        if ($secret === '') {
            return '';
        }
        if (strlen($secret) <= 8) {
            return str_repeat('*', strlen($secret));
        }

        return substr($secret, 0, 4).str_repeat('*', max(strlen($secret) - 8, 4)).substr($secret, -4);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeTencentCosPayload(array $payload): array
    {
        $map = [
            'secretId' => 'secret_id',
            'secretKey' => 'secret_key',
            'uploadPrefix' => 'upload_prefix',
            'useHttps' => 'use_https',
            'useUniqueName' => 'use_unique_name',
            'allowedExtensions' => 'allowed_extensions',
            'maxFileSize' => 'max_file_size',
            'overwriteEnabled' => 'overwrite_enabled',
            'isDefault' => 'is_default',
            'isEnabled' => 'is_enabled',
        ];

        foreach ($map as $from => $to) {
            if (! array_key_exists($to, $payload) && array_key_exists($from, $payload)) {
                $payload[$to] = $payload[$from];
            }
            unset($payload[$from]);
        }

        return $payload;
    }
}
