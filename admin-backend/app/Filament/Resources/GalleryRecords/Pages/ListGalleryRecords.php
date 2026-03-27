<?php

namespace App\Filament\Resources\GalleryRecords\Pages;

use App\Filament\Resources\GalleryRecords\GalleryRecordResource;
use App\Filament\Widgets\FeatureData\GalleryStats;
use Filament\Resources\Pages\ListRecords;

class ListGalleryRecords extends ListRecords
{
    protected static string $resource = GalleryRecordResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            GalleryStats::class,
        ];
    }
}

