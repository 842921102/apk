<?php

namespace App\Filament\Resources\DishRecipes\Pages;

use App\Filament\Resources\DishRecipes\DishRecipeResource;
use Filament\Resources\Pages\EditRecord;

class EditDishRecipe extends EditRecord
{
    protected static string $resource = DishRecipeResource::class;
}
