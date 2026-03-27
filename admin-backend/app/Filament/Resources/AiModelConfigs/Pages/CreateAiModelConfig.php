<?php

namespace App\Filament\Resources\AiModelConfigs\Pages;

use App\Filament\Resources\AiModelConfigs\AiModelConfigResource;
use App\Support\AdminActionLogger;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAiModelConfig extends CreateRecord
{
    protected static string $resource = AiModelConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['api_key'])) {
            throw ValidationException::withMessages([
                'api_key' => '请填写 API Key。',
            ]);
        }

        $data['created_by'] = (int) auth()->id();
        $data['updated_by'] = (int) auth()->id();

        return $data;
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

