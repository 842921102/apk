<?php

namespace App\Filament\Resources\Favorites;

use App\Filament\Resources\Favorites\Pages\ListFavorites;
use App\Filament\Resources\Favorites\Schemas\FavoriteInfolist;
use App\Filament\Resources\Favorites\Tables\FavoritesTable;
use App\Models\Favorite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FavoriteResource extends Resource
{
    protected static ?string $model = Favorite::class;

    protected static ?string $navigationLabel = '收藏管理';

    protected static string|UnitEnum|null $navigationGroup = '用户管理';

    protected static ?string $modelLabel = '收藏';

    protected static ?string $pluralModelLabel = '收藏';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 15;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function infolist(Schema $schema): Schema
    {
        return FavoriteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FavoritesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFavorites::route('/'),
        ];
    }

    /**
     * @return Builder<Favorite>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }
}
