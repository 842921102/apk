<?php

namespace App\Filament\Resources\FortuneCookingRecords;

use App\Filament\Resources\FeatureDataRecords\Schemas\FeatureDataRecordInfolist;
use App\Filament\Resources\FeatureDataRecords\Tables\FeatureDataRecordsTable;
use App\Filament\Resources\FortuneCookingRecords\Pages\ListFortuneCookingRecords;
use App\Models\FeatureDataRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FortuneCookingRecordResource extends Resource
{
    protected static ?string $model = FeatureDataRecord::class;

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?string $navigationLabel = '玄学厨房';

    protected static ?string $modelLabel = '玄学厨房记录';

    protected static ?string $pluralModelLabel = '玄学厨房记录';

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public static function infolist(Schema $schema): Schema
    {
        return FeatureDataRecordInfolist::configureFortuneCooking($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureDataRecordsTable::configureFortuneCooking($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFortuneCookingRecords::route('/'),
        ];
    }

    /**
     * @return Builder<FeatureDataRecord>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('feature_type', 'fortune_cooking');
    }
}

