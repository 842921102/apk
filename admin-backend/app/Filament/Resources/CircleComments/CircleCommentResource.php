<?php

namespace App\Filament\Resources\CircleComments;

use App\Filament\Resources\CircleComments\Pages\ListCircleComments;
use App\Filament\Resources\CircleComments\Schemas\CircleCommentInfolist;
use App\Filament\Resources\CircleComments\Tables\CircleCommentsTable;
use App\Models\CircleComment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CircleCommentResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = CircleComment::class;

    protected static ?string $navigationLabel = '评论管理';

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $modelLabel = '灵感评论';

    protected static ?string $pluralModelLabel = '灵感评论';

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CircleCommentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CircleCommentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCircleComments::route('/'),
        ];
    }

    /**
     * @return Builder<CircleComment>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'post']);
    }
}
