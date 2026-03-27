<?php

namespace App\Filament\Resources\AiModelConfigs\Pages;

use App\Filament\Resources\AiModelConfigs\AiModelConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiModelConfigs extends ListRecords
{
    protected static string $resource = AiModelConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

