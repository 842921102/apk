<?php

namespace App\Filament\Resources\InspirationComments\Pages;

use App\Filament\Resources\InspirationComments\InspirationCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListInspirationComments extends ListRecords
{
    protected static string $resource = InspirationCommentResource::class;
}
