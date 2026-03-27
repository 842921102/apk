<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TencentCosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class UploadController extends Controller
{
    public function cosConfig(TencentCosService $service): JsonResponse
    {
        $config = $service->getConfig();

        return response()->json([
            'data' => [
                'enabled' => (bool) ($config['is_enabled'] ?? false),
                'provider' => 'tencent_cos',
                'uploadPrefix' => (string) ($config['upload_prefix'] ?? ''),
                'useHttps' => (bool) ($config['use_https'] ?? true),
                'useUniqueName' => (bool) ($config['use_unique_name'] ?? true),
                'visibility' => (string) ($config['visibility'] ?? 'public'),
                'allowedExtensions' => $this->parseAllowedExtensions((string) ($config['allowed_extensions'] ?? '')),
                'maxFileSizeMb' => (int) ($config['max_file_size'] ?? 10),
            ],
        ]);
    }

    public function uploadToCos(Request $request, TencentCosService $service): JsonResponse
    {
        $config = $service->getConfig();
        if (! (bool) ($config['is_enabled'] ?? false)) {
            return response()->json(['message' => 'COS 配置未启用'], 422);
        }

        $validated = $request->validate([
            'file' => ['required', 'file'],
            'folder' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $maxBytes = max(1, (int) ($config['max_file_size'] ?? 10)) * 1024 * 1024;
        if ($file->getSize() !== false && $file->getSize() > $maxBytes) {
            return response()->json(['message' => '文件大小超过限制'], 422);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowed = $this->parseAllowedExtensions((string) ($config['allowed_extensions'] ?? ''));
        if ($allowed !== [] && ! in_array($ext, $allowed, true)) {
            return response()->json(['message' => '文件扩展名不允许'], 422);
        }

        $prefix = trim((string) ($config['upload_prefix'] ?? 'uploads'), '/');
        $folder = isset($validated['folder']) ? trim((string) $validated['folder'], '/') : '';
        $baseName = (string) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($baseName);
        if ($safeName === '') {
            $safeName = 'file';
        }
        $filename = ((bool) ($config['use_unique_name'] ?? true))
            ? (Str::uuid()->toString().($ext !== '' ? '.'.$ext : ''))
            : ($safeName.($ext !== '' ? '.'.$ext : ''));

        $segments = array_values(array_filter([$prefix, $folder], fn (string $v): bool => $v !== ''));
        $objectKey = ($segments === [] ? '' : implode('/', $segments).'/').$filename;

        $uploaded = $service->uploadFile($objectKey, $file, $config);

        return response()->json([
            'data' => [
                'provider' => 'tencent_cos',
                'key' => $uploaded['key'],
                'url' => $uploaded['url'],
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'ext' => $ext,
            ],
        ], 201);
    }

    /**
     * @return array<int, string>
     */
    private function parseAllowedExtensions(string $raw): array
    {
        return array_values(array_filter(array_map(
            static fn (string $v): string => strtolower(trim($v)),
            explode(',', $raw),
        ), static fn (string $v): bool => $v !== ''));
    }
}
