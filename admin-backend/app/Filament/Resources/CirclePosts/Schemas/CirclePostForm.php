<?php

namespace App\Filament\Resources\CirclePosts\Schemas;

use App\Support\CirclePostStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CirclePostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('关联用户')
                    ->schema([
                        Select::make('user_id')
                            ->label('发帖用户')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Section::make('帖子内容')
                    ->schema([
                        TextInput::make('title')
                            ->label('标题/一句话描述')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('描述文案')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label('正文')
                            ->required()
                            ->rows(12)
                            ->columnSpanFull(),
                        Repeater::make('images')
                            ->label('图片 URL 列表')
                            ->schema([
                                TextInput::make('url')
                                    ->label('图片地址')
                                    ->url()
                                    ->maxLength(2048),
                            ])
                            ->default([])
                            ->addActionLabel('添加图片')
                            ->reorderable()
                            ->columnSpanFull(),
                        TextInput::make('topic')
                            ->label('话题 / 分类')
                            ->maxLength(64)
                            ->default(''),
                        Select::make('source_type')
                            ->label('内容类型')
                            ->options([
                                'ai_generated' => 'AI生成',
                                'user_uploaded' => '用户实拍',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('publish_source')
                            ->label('发布来源')
                            ->options([
                                'ai_result' => 'AI结果页',
                                'manual_upload' => '手动上传',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('related_product_id')
                            ->label('关联商品')
                            ->relationship('relatedProduct', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('运营设置')
                    ->schema([
                        Select::make('status')
                            ->label('状态')
                            ->options(CirclePostStatus::labels())
                            ->required()
                            ->native(false),
                        Toggle::make('is_recommended')
                            ->label('推荐')
                            ->inline(false),
                        Toggle::make('is_pinned')
                            ->label('置顶')
                            ->inline(false),
                        DateTimePicker::make('published_at')
                            ->label('发布时间')
                            ->seconds(false)
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }
}
