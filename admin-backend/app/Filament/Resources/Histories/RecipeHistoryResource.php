<?php

namespace App\Filament\Resources\Histories;

use App\Filament\Resources\Histories\Pages\ListRecipeHistories;
use App\Filament\Resources\Histories\Schemas\RecipeHistoryInfolist;
use App\Filament\Resources\Histories\Tables\RecipeHistoriesTable;
use App\Models\RecipeHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecipeHistoryResource extends Resource
{
    protected static ?string $model = RecipeHistory::class;

    protected static ?string $navigationLabel = '历史管理';

    protected static ?string $modelLabel = '历史';

    protected static ?string $pluralModelLabel = '历史';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'histories';

    protected static ?int $navigationSort = 20;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecipeHistoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecipeHistoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecipeHistories::route('/'),
        ];
    }

    /**
     * @return Builder<RecipeHistory>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }
}

