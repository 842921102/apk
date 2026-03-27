<?php

namespace App\Filament\Resources\CirclePosts;

use App\Filament\Resources\CirclePosts\Pages\EditCirclePost;
use App\Filament\Resources\CirclePosts\Pages\ListCirclePosts;
use App\Filament\Resources\CirclePosts\Schemas\CirclePostForm;
use App\Filament\Resources\CirclePosts\Schemas\CirclePostInfolist;
use App\Filament\Resources\CirclePosts\Tables\CirclePostsTable;
use App\Models\CirclePost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CirclePostResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = CirclePost::class;

    protected static ?string $navigationLabel = '灵感内容';

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $modelLabel = '灵感内容';

    protected static ?string $pluralModelLabel = '灵感内容';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CirclePostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CirclePostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CirclePostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCirclePosts::route('/'),
            'edit' => EditCirclePost::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<CirclePost>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }
}
