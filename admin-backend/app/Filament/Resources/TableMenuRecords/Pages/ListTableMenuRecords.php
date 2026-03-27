<?php

namespace App\Filament\Resources\TableMenuRecords\Pages;

use App\Filament\Resources\TableMenuRecords\TableMenuRecordResource;
use App\Filament\Widgets\FeatureData\TableMenuStats;
use Filament\Resources\Pages\ListRecords;

class ListTableMenuRecords extends ListRecords
{
    protected static string $resource = TableMenuRecordResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TableMenuStats::class,
        ];
    }
}

