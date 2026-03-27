<?php

namespace App\Filament\Resources\AiModelConfigs;

use App\Filament\Resources\AiModelConfigs\Pages\CreateAiModelConfig;
use App\Filament\Resources\AiModelConfigs\Pages\EditAiModelConfig;
use App\Filament\Resources\AiModelConfigs\Pages\ListAiModelConfigs;
use App\Filament\Resources\AiModelConfigs\Schemas\AiModelConfigForm;
use App\Filament\Resources\AiModelConfigs\Schemas\AiModelConfigInfolist;
use App\Filament\Resources\AiModelConfigs\Tables\AiModelConfigsTable;
use App\Models\AiModelConfig;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AiModelConfigResource extends Resource
{
    protected static ?string $model = AiModelConfig::class;

    protected static string|UnitEnum|null $navigationGroup = '系统配置';

    protected static ?string $navigationLabel = '大模型配置';

    protected static ?string $modelLabel = '大模型配置';

    protected static ?string $pluralModelLabel = '大模型配置';

    protected static ?string $recordTitleAttribute = 'scene_code';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function form(Schema $schema): Schema
    {
        return AiModelConfigForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AiModelConfigInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiModelConfigsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiModelConfigs::route('/'),
            'create' => CreateAiModelConfig::route('/create'),
            'edit' => EditAiModelConfig::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<AiModelConfig>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['provider', 'model', 'creator', 'updater']);
    }
}

