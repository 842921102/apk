<?php

namespace App\Filament\Resources\CustomCuisineRecords\Pages;

use App\Filament\Resources\CustomCuisineRecords\CustomCuisineRecordResource;
use App\Filament\Widgets\FeatureData\CustomCuisineStats;
use Filament\Resources\Pages\ListRecords;

class ListCustomCuisineRecords extends ListRecords
{
    protected static string $resource = CustomCuisineRecordResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CustomCuisineStats::class,
        ];
    }
}

