<?php

namespace App\Filament\Resources\Histories\Schemas;

use App\Models\RecipeHistory;
use App\Support\FavoriteSourceType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RecipeHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('基本信息')
                ->schema([
                    TextEntry::make('id')->label('ID'),
                    TextEntry::make('title')->label('标题')->copyable(),
                    TextEntry::make('source_type')
                        ->label('来源类型')
                        ->formatStateUsing(fn (?string $state): string => self::sourceLabel($state)),
                    TextEntry::make('source_id')->label('来源 ID')->placeholder('—'),
                    TextEntry::make('cuisine')->label('菜系')->placeholder('—'),
                    TextEntry::make('user.name')->label('用户昵称')->placeholder('—'),
                    TextEntry::make('user.email')->label('用户邮箱')->placeholder('—'),
                    TextEntry::make('created_at')->label('创建时间')->dateTime(),
                ])
                ->columns(2),

            Section::make('食材（JSON）')
                ->schema([
                    TextEntry::make('ingredients')
                        ->label('ingredients')
                        ->formatStateUsing(fn ($state): string => self::jsonPreview($state)),
                ]),

            Section::make('请求参数（request_payload）')
                ->schema([
                    TextEntry::make('request_payload')
                        ->label('request_payload')
                        ->formatStateUsing(fn ($state): string => self::jsonPreview($state))
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('结果正文（response_content）')
                ->schema([
                    TextEntry::make('response_content')
                        ->label('response_content')
                        ->columnSpanFull(),
                ])
                ->collapsible(),

            Section::make('扩展字段（extra_payload）')
                ->schema([
                    TextEntry::make('extra_payload')
                        ->label('extra_payload')
                        ->formatStateUsing(fn ($state): string => self::jsonPreview($state))
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    private static function sourceLabel(?string $state): string
    {
        if ($state === null || $state === '') {
            return '—';
        }

        foreach (FavoriteSourceType::cases() as $case) {
            if ($case->value === $state) {
                return match ($case) {
                    FavoriteSourceType::TodayEat => '吃什么',
                    FavoriteSourceType::TableDesign => '满汉全席',
                    FavoriteSourceType::FortuneCooking => '玄学厨房',
                    FavoriteSourceType::SauceDesign => '酱料大师',
                    FavoriteSourceType::Gallery => '图鉴',
                };
            }
        }

        return $state;
    }

    private static function jsonPreview(mixed $state): string
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

