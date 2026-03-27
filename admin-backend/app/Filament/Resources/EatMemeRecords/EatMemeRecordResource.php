<?php

namespace App\Filament\Resources\EatMemeRecords;

use App\Filament\Resources\EatMemeRecords\Pages\ListEatMemeRecords;
use App\Filament\Resources\EatMemeRecords\Schemas\EatMemeRecordInfolist;
use App\Filament\Resources\EatMemeRecords\Tables\EatMemeRecordsTable;
use App\Models\EatMemeRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class EatMemeRecordResource extends Resource
{
    protected static ?string $model = EatMemeRecord::class;

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?string $navigationLabel = '吃么么';

    protected static ?string $modelLabel = '吃么么记录';

    protected static ?string $pluralModelLabel = '吃么么记录';

    protected static ?string $recordTitleAttribute = 'result_title';

    protected static ?int $navigationSort = 12;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function infolist(Schema $schema): Schema
    {
        return EatMemeRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EatMemeRecordsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEatMemeRecords::route('/'),
        ];
    }

    /**
     * @return Builder<EatMemeRecord>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}

