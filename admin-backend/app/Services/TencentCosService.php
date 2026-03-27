<?php

namespace App\Services;

use App\Models\BusinessConfig;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class TencentCosService
{
    public const CONFIG_KEY = 'tencent_cos';

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $row = BusinessConfig::query()->where('config_key', self::CONFIG_KEY)->first();
        $value = is_array($row?->config_value) ? $row->config_value : [];

        return array_merge($this->defaultConfig(), $value, [
            'is_enabled' => (bool) ($row?->is_enabled ?? false),
            'last_tested_at' => isset($value['last_tested_at']) ? (string) $value['last_tested_at'] : null,
            'last_test_result' => isset($value['last_test_result']) ? (string) $value['last_test_result'] : '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveConfig(array $payload): BusinessConfig
    {
        $current = $this->getConfig();
        $merged = array_merge($current, Arr::only($payload, array_keys($this->defaultConfig())));

        if (array_key_exists('secret_key', $payload) && is_string($payload['secret_key']) && trim($payload['secret_key']) !== '') {
            $merged['secret_key'] = trim((string) $payload['secret_key']);
        } else {
            $merged['secret_key'] = (string) ($current['secret_key'] ?? '');
        }

        $row = BusinessConfig::query()->firstOrNew(['config_key' => self::CONFIG_KEY]);
        $row->config_name = '腾讯云对象存储';
        $row->config_group = 'storage';
        $row->is_enabled = (bool) ($payload['is_enabled'] ?? $current['is_enabled'] ?? false);
        $row->sort = (int) ($payload['sort'] ?? $row->sort ?? 0);
        $row->remark = isset($payload['remark']) ? (string) $payload['remark'] : ($row->remark ?? null);
        $row->config_value = Arr::only($merged, array_keys($this->defaultConfig()));
        $row->save();

        return $row;
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return array{ok: bool, message: string}
     */
    public function testConnection(?array $config = null): array
    {
        try {
            $client = $this->makeClient($config);
            $cfg = $this->mergeWithDefault($config);
            $client->headBucket(['Bucket' => (string) $cfg['bucket']]);

            $this->updateLastTest('连接成功');

            return ['ok' => true, 'message' => '连接成功'];
        } catch (Throwable $e) {
            $msg = '连接失败: '.$e->getMessage();
            $this->updateLastTest($msg);

            return ['ok' => false, 'message' => $msg];
        }
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return array{ok: bool, message: string, file_url: string|null, object_key: string|null}
     */
    public function testUpload(?array $config = null): array
    {
        try {
            $cfg = $this->mergeWithDefault($config);
            $objectKey = $this->buildObjectKey((string) ($cfg['upload_prefix'] ?? ''), 'cos-test-'.Date::now()->format('YmdHis').'.txt', (bool) $cfg['use_unique_name']);
            $result = $this->uploadFile($objectKey, "COS test file at ".Date::now()->toDateTimeString(), $cfg);

            $msg = '测试上传成功';
            $this->updateLastTest($msg);

            return [
                'ok' => true,
                'message' => $msg,
                'file_url' => $result['url'],
                'object_key' => $objectKey,
            ];
        } catch (Throwable $e) {
            $msg = '测试上传失败: '.$e->getMessage();
            $this->updateLastTest($msg);

            return [
                'ok' => false,
                'message' => $msg,
                'file_url' => null,
                'object_key' => null,
            ];
        }
    }

    /**
     * @param  string|UploadedFile  $file
     * @param  array<string, mixed>|null  $config
     * @return array{key: string, url: string}
     */
    public function uploadFile(string $objectKey, string|UploadedFile $file, ?array $config = null): array
    {
        $cfg = $this->mergeWithDefault($config);
        $client = $this->makeClient($cfg);
        $body = $file instanceof UploadedFile ? fopen($file->getRealPath(), 'rb') : $file;

        $result = $client->putObject([
            'Bucket' => (string) $cfg['bucket'],
            'Key' => $objectKey,
            'Body' => $body,
            'ACL' => ((string) $cfg['visibility']) === 'private' ? 'private' : 'public-read',
        ]);

        if ($result === null) {
            throw new RuntimeException('上传结果为空');
        }

        return [
            'key' => $objectKey,
            'url' => $this->getFileUrl($objectKey, $cfg),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function deleteFile(string $objectKey, ?array $config = null): bool
    {
        try {
            $cfg = $this->mergeWithDefault($config);
            $client = $this->makeClient($cfg);
            $client->deleteObject([
                'Bucket' => (string) $cfg['bucket'],
                'Key' => $objectKey,
            ]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>|null  $config
     */
    public function getFileUrl(string $objectKey, ?array $config = null): string
    {
        $cfg = $this->mergeWithDefault($config);
        $domain = trim((string) ($cfg['domain'] ?? ''));
        $scheme = ((bool) ($cfg['use_https'] ?? true)) ? 'https' : 'http';
        $key = ltrim($objectKey, '/');

        if ($domain !== '') {
            $host = preg_replace('#^https?://#', '', $domain) ?? $domain;

            return $scheme.'://'.$host.'/'.$key;
        }

        return sprintf('%s://%s.cos.%s.myqcloud.com/%s', $scheme, $cfg['bucket'], $cfg['region'], $key);
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return object
     */
    private function makeClient(?array $config = null): object
    {
        if (! class_exists(\Qcloud\Cos\Client::class)) {
            throw new RuntimeException('未安装 qcloud/cos-sdk-v5，请先安装 Composer 依赖。');
        }

        $cfg = $this->mergeWithDefault($config);
        $required = ['secret_id', 'secret_key', 'bucket', 'region'];
        foreach ($required as $field) {
            if (trim((string) ($cfg[$field] ?? '')) === '') {
                throw new RuntimeException("缺少 COS 配置字段: {$field}");
            }
        }

        return new \Qcloud\Cos\Client([
            'region' => (string) $cfg['region'],
            'schema' => ((bool) $cfg['use_https']) ? 'https' : 'http',
            'credentials' => [
                'secretId' => (string) $cfg['secret_id'],
                'secretKey' => (string) $cfg['secret_key'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'secret_id' => '',
            'secret_key' => '',
            'bucket' => '',
            'region' => '',
            'domain' => '',
            'upload_prefix' => 'uploads',
            'use_https' => true,
            'use_unique_name' => true,
            'visibility' => 'public',
            'allowed_extensions' => 'jpg,jpeg,png,webp,gif,mp4,pdf',
            'max_file_size' => 10,
            'overwrite_enabled' => false,
            'is_default' => true,
            'last_tested_at' => null,
            'last_test_result' => '',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>
     */
    private function mergeWithDefault(?array $config): array
    {
        if ($config === null) {
            return $this->getConfig();
        }

        return array_merge($this->getConfig(), Arr::only($config, array_keys($this->defaultConfig())));
    }

    private function buildObjectKey(string $prefix, string $filename, bool $useUniqueName): string
    {
        $safePrefix = trim($prefix, '/');
        $finalName = $filename;
        if ($useUniqueName) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $base = Str::uuid()->toString();
            $finalName = $ext !== '' ? ($base.'.'.$ext) : $base;
        }

        return $safePrefix === '' ? $finalName : ($safePrefix.'/'.$finalName);
    }

    private function updateLastTest(string $result): void
    {
        $row = BusinessConfig::query()->firstOrNew(['config_key' => self::CONFIG_KEY]);
        $value = is_array($row->config_value) ? $row->config_value : [];
        $value['last_tested_at'] = Carbon::now()->toDateTimeString();
        $value['last_test_result'] = $result;
        $row->config_name = $row->config_name ?: '腾讯云对象存储';
        $row->config_group = $row->config_group ?: 'storage';
        $row->config_value = array_merge($this->defaultConfig(), $value);
        $row->save();
    }
}
