<?php

namespace App\Filament\Resources\FeatureDataRecords\Schemas;

use App\Models\FeatureDataRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeatureDataRecordInfolist
{
    public static function configureTableMenu(Schema $schema): Schema
    {
        return $schema->components([
            self::baseSection(),
            Section::make('业务详情')->schema([
                TextEntry::make('menu_count')
                    ->label('生成菜数')
                    ->state(fn (FeatureDataRecord $record): int => count(data_get($record->input_payload, 'config.menus', []))),
                TextEntry::make('category_count')
                    ->label('菜系数')
                    ->state(fn (FeatureDataRecord $record): int => count(data_get($record->input_payload, 'config.categories', []))),
                TextEntry::make('people_count')
                    ->label('就餐人数')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'config.people_count', '—')),
                TextEntry::make('result_summary')->label('结果摘要')->placeholder('—')->columnSpanFull(),
            ])->columns(2),
            self::rawSection(),
        ]);
    }

    public static function configureFortuneCooking(Schema $schema): Schema
    {
        return $schema->components([
            self::baseSection(),
            Section::make('业务详情')->schema([
                TextEntry::make('fortune_type')
                    ->label('运势类型')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'fortuneType', '—')),
                TextEntry::make('mood')
                    ->label('心情')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'mood', '—')),
                TextEntry::make('number')
                    ->label('幸运数字')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'number', '—')),
                TextEntry::make('locale')
                    ->label('语言')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'locale', '—')),
                TextEntry::make('result_summary')->label('结果摘要')->placeholder('—')->columnSpanFull(),
            ])->columns(1),
            self::rawSection(),
        ]);
    }

    public static function configureSauceDesign(Schema $schema): Schema
    {
        return $schema->components([
            self::baseSection(),
            Section::make('业务详情')->schema([
                TextEntry::make('taste')
                    ->label('口味偏好')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.taste', '—')),
                TextEntry::make('scene')
                    ->label('使用场景')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.scene', '—')),
                TextEntry::make('sauce_name')
                    ->label('酱料名称')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'sauce_name', '—')),
                TextEntry::make('result_summary')->label('结果摘要')->placeholder('—')->columnSpanFull(),
            ])->columns(2),
            self::rawSection(),
        ]);
    }

    public static function configureGallery(Schema $schema): Schema
    {
        return $schema->components([
            self::baseSection(),
            Section::make('业务详情')->schema([
                TextEntry::make('gallery_size')
                    ->label('图鉴数量')
                    ->state(fn (FeatureDataRecord $record): string => (string) ($record->result_summary ?? '—')),
                TextEntry::make('result_summary')->label('结果摘要')->placeholder('—')->columnSpanFull(),
            ])->columns(1),
            self::rawSection(),
        ]);
    }

    public static function configureCustomCuisine(Schema $schema): Schema
    {
        return $schema->components([
            self::baseSection(),
            Section::make('业务详情')->schema([
                TextEntry::make('taste')
                    ->label('口味要求')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.taste', '—')),
                TextEntry::make('avoid')
                    ->label('忌口')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->input_payload, 'preferences.avoid', '—')),
                TextEntry::make('cuisine')
                    ->label('菜系')
                    ->state(fn (FeatureDataRecord $record): string => (string) data_get($record->result_payload, 'cuisine', '—')),
                TextEntry::make('ingredient_count')
                    ->label('食材数')
                    ->state(fn (FeatureDataRecord $record): int => count(data_get($record->result_payload, 'ingredients', []))),
                TextEntry::make('result_summary')->label('结果摘要')->placeholder('—')->columnSpanFull(),
            ])->columns(2),
            self::rawSection(),
        ]);
    }

    private static function baseSection(): Section
    {
        return Section::make('基础信息')->schema([
            TextEntry::make('id')->label('编号')->copyable(),
            TextEntry::make('feature_type')->label('功能类型')->badge(),
            TextEntry::make('sub_type')->label('动作类型')->placeholder('—'),
            TextEntry::make('status')->label('状态')->badge(),
            TextEntry::make('title')->label('标题')->placeholder('—'),
            TextEntry::make('channel')->label('渠道')->placeholder('—'),
            TextEntry::make('user_id')->label('用户编号')->placeholder('—')->copyable(),
            TextEntry::make('created_at')->label('创建时间')->dateTime(),
        ])->columns(2);
    }

    private static function rawSection(): Section
    {
        return Section::make('原始请求与结果')->schema([
            TextEntry::make('input_payload')
                ->label('请求参数')
                ->formatStateUsing(fn ($state): string => self::jsonEncodePayload($state))
                ->columnSpanFull(),
            TextEntry::make('result_payload')
                ->label('结果数据')
                ->formatStateUsing(fn ($state): string => self::jsonEncodePayload($state))
                ->columnSpanFull(),
            TextEntry::make('error_message')->label('错误信息')->placeholder('—')->columnSpanFull(),
        ])->collapsible();
    }

    /**
     * KeyValueEntry 仅适合「键 => 标量」结构；嵌套数组会触发 htmlspecialchars 类型错误。
     */
    private static function jsonEncodePayload(mixed $state): string
    {
        if ($state === null) {
            return '—';
        }
        if (is_string($state)) {
            return $state;
        }

        $enc = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $enc !== false ? $enc : '—';
    }
}
