<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|UnitEnum|null $navigationGroup = '商城管理';

    protected static ?string $navigationLabel = '商品管理';

    protected static ?string $modelLabel = '商品';

    protected static ?string $pluralModelLabel = '商品';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('商品信息')
                    ->schema([
                        TextInput::make('name')->label('商品名')->required()->maxLength(200),
                        FileUpload::make('cover_image')
                            ->label('主图')
                            ->image()
                            ->disk('public')
                            ->directory('products/covers')
                            ->visibility('public')
                            ->required(),
                        FileUpload::make('images')
                            ->label('轮播图')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('products/gallery')
                            ->visibility('public')
                            ->default([]),
                        TextInput::make('price')->label('售价（分）')->required()->numeric()->minValue(0),
                        TextInput::make('original_price')->label('原价（分）')->numeric()->minValue(0)->nullable(),
                        TextInput::make('stock')->label('库存')->required()->numeric()->minValue(0),
                        TextInput::make('sales_count')->label('销量')->numeric()->minValue(0)->default(0)->disabled()->dehydrated(false),
                        Textarea::make('description')->label('商品简介')->rows(3)->columnSpanFull(),
                        Textarea::make('detail_content')->label('商品详情说明')->rows(8)->columnSpanFull(),
                        Select::make('status')
                            ->label('状态')
                            ->options([
                                'draft' => '草稿',
                                'on_sale' => '上架',
                                'off_sale' => '下架',
                                'deleted' => '已删除',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('sort')->label('排序')->required()->numeric()->minValue(0)->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('deleted_at'))
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->copyable(),
                ImageColumn::make('cover_image')->label('商品主图')->disk('public')->square()->imageSize(42),
                TextColumn::make('name')->label('商品名称')->searchable()->limit(26),
                TextColumn::make('price')->label('售价')->formatStateUsing(fn (int $state): string => '¥'.number_format($state / 100, 2)),
                TextColumn::make('original_price')->label('原价')->formatStateUsing(fn (?int $state): string => $state ? '¥'.number_format($state / 100, 2) : '—'),
                TextColumn::make('stock')->label('库存')->numeric()->sortable(),
                TextColumn::make('sales_count')->label('销量')->numeric()->sortable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'on_sale' => '上架',
                        'off_sale' => '下架',
                        'deleted' => '已删除',
                        default => '草稿',
                    }),
                TextColumn::make('sort')->label('排序')->numeric()->sortable(),
                TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('on_sale')
                    ->label('上架')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Product $record): bool => $record->update(['status' => 'on_sale']))
                    ->visible(fn (Product $record): bool => $record->status !== 'on_sale'),
                Action::make('off_sale')
                    ->label('下架')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Product $record): bool => $record->update(['status' => 'off_sale']))
                    ->visible(fn (Product $record): bool => $record->status === 'on_sale'),
                DeleteAction::make()->label('删除'),
            ])
            ->recordUrl(fn (Product $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
