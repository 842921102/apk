<?php

namespace App\Filament\Resources\CircleComments\Pages;

use App\Filament\Resources\CircleComments\CircleCommentResource;
use Filament\Resources\Pages\ListRecords;

class ListCircleComments extends ListRecords
{
    protected static string $resource = CircleCommentResource::class;
}
