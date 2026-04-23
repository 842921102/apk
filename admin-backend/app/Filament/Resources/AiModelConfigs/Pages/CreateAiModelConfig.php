<?php

namespace App\Filament\Resources\AiModelConfigs\Pages;

use App\Filament\Resources\AiModelConfigs\AiModelConfigResource;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Support\AdminActionLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateAiModelConfig extends CreateRecord
{
    protected static string $resource = AiModelConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->resolveProviderAndModelIds($data);

        if (empty($data['api_key'])) {
            throw ValidationException::withMessages([
                'api_key' => '请填写接口密钥。',
            ]);
        }

        $data['created_by'] = (int) auth()->id();
        $data['updated_by'] = (int) auth()->id();

        return $data;
    }

    private function resolveProviderAndModelIds(array $data): array
    {
        $manualProviderName = trim((string) ($data['provider_name_manual'] ?? ''));
        $manualModelName = trim((string) ($data['model_name_manual'] ?? ''));

        if ($manualProviderName !== '') {
            $provider = AiProvider::query()->firstOrCreate(
                ['provider_name' => $manualProviderName],
                [
                    'provider_code' => $this->makeUniqueProviderCode($manualProviderName),
                    'provider_type' => 'multi',
                    'base_url' => 'https://api.openai.com/v1',
                    'is_enabled' => true,
                ],
            );
            $data['provider_id'] = (int) $provider->getKey();
        }

        if (empty($data['provider_id'])) {
            throw ValidationException::withMessages([
                'provider_id' => '请选择供应商或手动填写供应商。',
            ]);
        }

        if ($manualModelName !== '') {
            $model = AiModel::query()->firstOrCreate(
                [
                    'provider_id' => (int) $data['provider_id'],
                    'model_name' => $manualModelName,
                ],
                [
                    'model_code' => $this->makeUniqueModelCode((int) $data['provider_id'], $manualModelName),
                    'model_type' => 'text',
                    'is_enabled' => true,
                    'is_default' => false,
                    'supports_temperature' => true,
                    'supports_timeout' => true,
                ],
            );
            $data['model_id'] = (int) $model->getKey();
        }

        if (empty($data['model_id'])) {
            throw ValidationException::withMessages([
                'model_id' => '请选择模型或手动填写模型。',
            ]);
        }

        unset($data['provider_name_manual'], $data['model_name_manual']);

        return $data;
    }

    private function makeUniqueProviderCode(string $fallbackName): string
    {
        $base = Str::of($fallbackName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($base === '') {
            $base = 'provider';
        }

        $code = $base;
        $suffix = 1;
        while (AiProvider::query()->where('provider_code', $code)->exists()) {
            $code = "{$base}_{$suffix}";
            $suffix++;
        }

        return $code;
    }

    private function makeUniqueModelCode(int $providerId, string $fallbackName): string
    {
        $base = Str::of($fallbackName)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '_')
            ->trim('_')
            ->value();

        if ($base === '') {
            $base = 'model';
        }

        $code = $base;
        $suffix = 1;
        while (AiModel::query()->where('provider_id', $providerId)->where('model_code', $code)->exists()) {
            $code = "{$base}_{$suffix}";
            $suffix++;
        }

        return $code;
    }

    protected function afterCreate(): void
    {
        AdminActionLogger::record('ai_model_config.created', $this->getRecord(), [
            'scene_code' => $this->getRecord()->scene_code,
            'provider_id' => $this->getRecord()->provider_id,
            'model_id' => $this->getRecord()->model_id,
        ]);
    }
}
