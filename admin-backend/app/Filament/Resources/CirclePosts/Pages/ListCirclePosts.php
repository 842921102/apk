<?php

namespace App\Filament\Resources\CirclePosts\Pages;

use App\Filament\Resources\CirclePosts\CirclePostResource;
use Filament\Resources\Pages\ListRecords;

class ListCirclePosts extends ListRecords
{
    protected static string $resource = CirclePostResource::class;
}
