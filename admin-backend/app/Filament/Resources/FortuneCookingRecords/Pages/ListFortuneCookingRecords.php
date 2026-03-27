<?php

namespace App\Filament\Resources\FortuneCookingRecords\Pages;

use App\Filament\Resources\FortuneCookingRecords\FortuneCookingRecordResource;
use App\Filament\Widgets\FeatureData\FortuneCookingStats;
use Filament\Resources\Pages\ListRecords;

class ListFortuneCookingRecords extends ListRecords
{
    protected static string $resource = FortuneCookingRecordResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FortuneCookingStats::class,
        ];
    }
}

