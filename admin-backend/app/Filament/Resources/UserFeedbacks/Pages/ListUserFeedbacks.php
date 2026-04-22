<?php

namespace App\Filament\Resources\UserFeedbacks\Pages;

use App\Filament\Resources\UserFeedbacks\UserFeedbackResource;
use Filament\Resources\Pages\ListRecords;

class ListUserFeedbacks extends ListRecords
{
    protected static string $resource = UserFeedbackResource::class;
}
