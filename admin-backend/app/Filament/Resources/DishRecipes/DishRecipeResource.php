<?php

namespace App\Filament\Resources\DishRecipes;

use App\Filament\Resources\DishRecipes\Pages\CreateDishRecipe;
use App\Filament\Resources\DishRecipes\Pages\EditDishRecipe;
use App\Filament\Resources\DishRecipes\Pages\ListDishRecipes;
use App\Models\DishRecipe;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DishRecipeResource extends Resource
{
    protected static ?string $model = DishRecipe::class;

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $navigationLabel = '菜谱做法';

    protected static ?string $modelLabel = '菜谱';

    protected static ?string $pluralModelLabel = '菜谱做法';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 12;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基础信息')
                    ->schema([
                        TextInput::make('title')
                            ->label('菜名')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('dish_key')
                            ->label('匹配键（可选）')
                            ->maxLength(128),
                        Textarea::make('description')
                            ->label('菜品简介')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('启用')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('食材与调料')
                    ->schema([
                        TagsInput::make('ingredients')
                            ->label('主要食材')
                            ->placeholder('回车添加')
                            ->columnSpanFull(),
                        TagsInput::make('seasonings')
                            ->label('辅料 / 调料')
                            ->placeholder('回车添加')
                            ->columnSpanFull(),
                    ]),
                Section::make('做法与时间')
                    ->schema([
                        Repeater::make('steps')
                            ->label('做法步骤')
                            ->schema([
                                Textarea::make('content')
                                    ->label('步骤说明')
                                    ->rows(4)
                                    ->required(),
                            ])
                            ->default([])
                            ->reorderable()
                            ->addActionLabel('添加步骤')
                            ->columnSpanFull(),
                        TextInput::make('cooking_time')
                            ->label('预计耗时')
                            ->maxLength(64)
                            ->placeholder('如：约25分钟'),
                        Select::make('difficulty')
                            ->label('难度')
                            ->options([
                                '简单' => '简单',
                                '中等' => '中等',
                                '较难' => '较难',
                            ])
                            ->native(false)
                            ->placeholder('可选'),
                    ])
                    ->columns(2),
                Section::make('小贴士')
                    ->schema([
                        TagsInput::make('tips')
                            ->label('小贴士')
                            ->placeholder('回车添加一条')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->label('菜名')->searchable()->limit(24),
                TextColumn::make('dish_key')->label('匹配键')->toggleable()->limit(20),
                IconColumn::make('is_active')->label('启用')->boolean(),
                TextColumn::make('cooking_time')->label('耗时')->toggleable(),
                TextColumn::make('difficulty')->label('难度')->toggleable(),
                TextColumn::make('updated_at')->label('更新')->dateTime()->sortable(),
            ])
            ->recordActions([
                DeleteAction::make()->label('删除'),
            ])
            ->recordUrl(fn (DishRecipe $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDishRecipes::route('/'),
            'create' => CreateDishRecipe::route('/create'),
            'edit' => EditDishRecipe::route('/{record}/edit'),
        ];
    }
}
