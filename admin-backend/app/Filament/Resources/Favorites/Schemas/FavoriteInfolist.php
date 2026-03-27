<?php

namespace App\Filament\Resources\Favorites\Schemas;

use App\Support\FavoriteSourceType;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FavoriteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本信息')
                    ->schema([
                        TextEntry::make('id')->label('编号')->copyable(),
                        TextEntry::make('title')->label('标题')->copyable(),
                        TextEntry::make('source_type')
                            ->label('来源类型')
                            ->formatStateUsing(fn (?string $state): string => self::sourceLabel($state)),
                        TextEntry::make('source_id')->label('来源编号')->placeholder('—'),
                        TextEntry::make('cuisine')->label('菜系')->placeholder('—'),
                        TextEntry::make('user.name')->label('用户昵称'),
                        TextEntry::make('user.email')->label('用户邮箱')->copyable(),
                        TextEntry::make('user.id')->label('用户编号')->copyable(),
                        TextEntry::make('created_at')->label('创建时间')->dateTime(),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime(),
                    ])
                    ->columns(2),
                Section::make('食材（结构化数据）')
                    ->schema([
                        TextEntry::make('ingredients')
                            ->label('食材数据')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state): string => self::jsonPreview($state)),
                    ]),
                Section::make('正文')
                    ->schema([
                        TextEntry::make('recipe_content')
                            ->label('正文内容')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                Section::make('扩展字段')
                    ->schema([
                        TextEntry::make('extra_payload')
                            ->label('扩展数据')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state): string => self::jsonPreview($state)),
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
