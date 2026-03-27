<?php

namespace App\Filament\Resources\AiModelConfigs\Schemas;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Support\AiScene;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AiModelConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('scene_code')
                ->label('场景')
                ->options(AiScene::options())
                ->required()
                ->native(false),
            Select::make('provider_id')
                ->label('供应商')
                ->options(fn () => AiProvider::query()->orderBy('provider_name')->pluck('provider_name', 'id'))
                ->searchable()
                ->required()
                ->native(false),
            Select::make('model_id')
                ->label('模型')
                ->options(fn () => AiModel::query()->orderBy('model_name')->pluck('model_name', 'id'))
                ->searchable()
                ->required()
                ->native(false),
            TextInput::make('api_key')
                ->label('接口密钥')
                ->password()
                ->revealable()
                ->helperText('编辑时留空表示不替换原接口密钥。')
                ->maxLength(2048),
            TextInput::make('base_url_override')
                ->label('基础地址覆盖')
                ->maxLength(512)
                ->placeholder('可空；为空时使用供应商基础地址'),
            TextInput::make('temperature')
                ->label('温度参数')
                ->numeric()
                ->minValue(0)
                ->maxValue(2)
                ->step(0.01),
            TextInput::make('timeout_ms')
                ->label('超时时间（毫秒）')
                ->numeric()
                ->minValue(1000)
                ->maxValue(120000)
                ->step(1000),
            Toggle::make('is_enabled')
                ->label('启用')
                ->default(true),
            Toggle::make('is_default')
                ->label('默认')
                ->default(true),
            Textarea::make('remark')
                ->label('备注')
                ->rows(3)
                ->maxLength(2000),
        ]);
    }
}
