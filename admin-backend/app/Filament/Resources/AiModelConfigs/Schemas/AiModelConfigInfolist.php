<?php

namespace App\Filament\Resources\AiModelConfigs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiModelConfigInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('基础')
                ->schema([
                    TextEntry::make('id')->label('编号'),
                    TextEntry::make('scene_code')->label('场景'),
                    TextEntry::make('provider.provider_name')->label('供应商'),
                    TextEntry::make('model.model_name')->label('模型'),
                    TextEntry::make('key_masked')->label('接口密钥（掩码）')->placeholder('未配置'),
                    TextEntry::make('base_url_override')->label('基础地址覆盖')->placeholder('—'),
                    TextEntry::make('temperature')->label('温度参数')->placeholder('—'),
                    TextEntry::make('timeout_ms')->label('超时时间（毫秒）')->placeholder('—'),
                    TextEntry::make('fallback_model_codes')->label('降级模型链')->placeholder('—'),
                    TextEntry::make('is_enabled')
                        ->label('启用')
                        ->formatStateUsing(fn (?bool $state): string => $state ? '是' : '否')
                        ->badge(),
                    TextEntry::make('is_default')
                        ->label('默认')
                        ->formatStateUsing(fn (?bool $state): string => $state ? '是' : '否')
                        ->badge(),
                    TextEntry::make('remark')->label('备注')->placeholder('—'),
                ])
                ->columns(2),
        ]);
    }
}
