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
                    TextEntry::make('id')->label('ID'),
                    TextEntry::make('scene_code')->label('场景'),
                    TextEntry::make('provider.provider_name')->label('供应商'),
                    TextEntry::make('model.model_name')->label('模型'),
                    TextEntry::make('key_masked')->label('API Key（掩码）')->placeholder('未配置'),
                    TextEntry::make('base_url_override')->label('URL 覆盖')->placeholder('—'),
                    TextEntry::make('temperature')->label('temperature')->placeholder('—'),
                    TextEntry::make('timeout_ms')->label('timeout_ms')->placeholder('—'),
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

