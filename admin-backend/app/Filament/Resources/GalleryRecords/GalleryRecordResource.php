<?php

namespace App\Filament\Resources\GalleryRecords;

use App\Filament\Resources\FeatureDataRecords\Schemas\FeatureDataRecordInfolist;
use App\Filament\Resources\FeatureDataRecords\Tables\FeatureDataRecordsTable;
use App\Filament\Resources\GalleryRecords\Pages\ListGalleryRecords;
use App\Models\FeatureDataRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class GalleryRecordResource extends Resource
{
    protected static ?string $model = FeatureDataRecord::class;

    protected static string|UnitEnum|null $navigationGroup = '数据管理';

    protected static ?string $navigationLabel = '图鉴';

    protected static ?string $modelLabel = '图鉴记录';

    protected static ?string $pluralModelLabel = '图鉴记录';

    protected static ?int $navigationSort = 23;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function infolist(Schema $schema): Schema
    {
        return FeatureDataRecordInfolist::configureGallery($schema);
    }

    public static function table(Table $table): Table
    {
        return FeatureDataRecordsTable::configureGallery($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleryRecords::route('/'),
        ];
    }

    /**
     * @return Builder<FeatureDataRecord>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('feature_type', 'gallery');
    }
}

