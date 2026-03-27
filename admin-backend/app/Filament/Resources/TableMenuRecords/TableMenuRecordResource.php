<?php

namespace App\Filament\Resources\TableMenuRecords;

use App\Filament\Resources\FeatureDataRecords\Schemas\FeatureDataRecordInfolist;
use App\Filament\Resources\FeatureDataRecords\Tables\FeatureDataRecordsTable;
use App\Filament\Resources\TableMenuRecords\Pages\ListTableMenuRecords;
use App\Models\FeatureDataRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TableMenuRecordResource extends Resource
{
    protected static ?string $model = FeatureDataRecord::class;

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?string $navigationLabel = '一桌好菜';

    protected static ?string $modelLabel = '一桌好菜记录';

    protected static ?string $pluralModelLabel = '一桌好菜记录';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function infolist(Schema $schema): Schema
    {
        return FeatureDataRecordInfolist::configureTableMenu($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureDataRecordsTable::configureTableMenu($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTableMenuRecords::route('/'),
        ];
    }

    /**
     * @return Builder<FeatureDataRecord>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('feature_type', 'table_menu');
    }
}

