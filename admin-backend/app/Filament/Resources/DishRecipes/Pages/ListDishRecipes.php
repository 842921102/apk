<?php

namespace App\Filament\Resources\DishRecipes\Pages;

use App\Filament\Resources\DishRecipes\DishRecipeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDishRecipes extends ListRecords
{
    protected static string $resource = DishRecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('新建菜谱'),
        ];
    }
}
