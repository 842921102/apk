<?php

namespace App\Filament\Resources\CustomCuisineRecords;

use App\Filament\Resources\CustomCuisineRecords\Pages\ListCustomCuisineRecords;
use App\Filament\Resources\FeatureDataRecords\Schemas\FeatureDataRecordInfolist;
use App\Filament\Resources\FeatureDataRecords\Tables\FeatureDataRecordsTable;
use App\Models\FeatureDataRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomCuisineRecordResource extends Resource
{
    protected static ?string $model = FeatureDataRecord::class;

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?string $navigationLabel = '自定义菜系';

    protected static ?string $modelLabel = '自定义菜系记录';

    protected static ?string $pluralModelLabel = '自定义菜系记录';

    protected static ?int $navigationSort = 24;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function infolist(Schema $schema): Schema
    {
        return FeatureDataRecordInfolist::configureCustomCuisine($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureDataRecordsTable::configureCustomCuisine($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomCuisineRecords::route('/'),
        ];
    }

    /**
     * @return Builder<FeatureDataRecord>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('feature_type', 'custom_cuisine');
    }
}

