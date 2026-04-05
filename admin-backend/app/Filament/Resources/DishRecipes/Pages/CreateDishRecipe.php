<?php

namespace App\Filament\Resources\DishRecipes\Pages;

use App\Filament\Resources\DishRecipes\DishRecipeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDishRecipe extends CreateRecord
{
    protected static string $resource = DishRecipeResource::class;
}
