<?php

namespace App\Filament\Resources\SauceDesignRecords;

use App\Filament\Resources\FeatureDataRecords\Schemas\FeatureDataRecordInfolist;
use App\Filament\Resources\FeatureDataRecords\Tables\FeatureDataRecordsTable;
use App\Filament\Resources\SauceDesignRecords\Pages\ListSauceDesignRecords;
use App\Models\FeatureDataRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SauceDesignRecordResource extends Resource
{
    protected static ?string $model = FeatureDataRecord::class;

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?string $navigationLabel = '酱料大师';

    protected static ?string $modelLabel = '酱料大师记录';

    protected static ?string $pluralModelLabel = '酱料大师记录';

    protected static ?int $navigationSort = 22;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    public static function infolist(Schema $schema): Schema
    {
        return FeatureDataRecordInfolist::configureSauceDesign($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureDataRecordsTable::configureSauceDesign($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSauceDesignRecords::route('/'),
        ];
    }

    /**
     * @return Builder<FeatureDataRecord>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('feature_type', 'sauce_design');
    }
}

