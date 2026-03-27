<?php

namespace App\Filament\Resources\SauceDesignRecords\Pages;

use App\Filament\Resources\SauceDesignRecords\SauceDesignRecordResource;
use App\Filament\Widgets\FeatureData\SauceDesignStats;
use Filament\Resources\Pages\ListRecords;

class ListSauceDesignRecords extends ListRecords
{
    protected static string $resource = SauceDesignRecordResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SauceDesignStats::class,
        ];
    }
}

