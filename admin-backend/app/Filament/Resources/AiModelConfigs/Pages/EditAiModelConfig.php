<?php

namespace App\Filament\Resources\AiModelConfigs\Pages;

use App\Filament\Resources\AiModelConfigs\AiModelConfigResource;
use App\Support\AdminActionLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiModelConfig extends EditRecord
{
    protected static string $resource = AiModelConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('删除'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['api_key'])) {
            unset($data['api_key']); // 留空不覆盖原 key
        }

        $data['updated_by'] = (int) auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        AdminActionLogger::record('ai_model_config.updated', $this->getRecord(), [
            'scene_code' => $this->getRecord()->scene_code,
            'provider_id' => $this->getRecord()->provider_id,
            'model_id' => $this->getRecord()->model_id,
        ]);
    }
}
