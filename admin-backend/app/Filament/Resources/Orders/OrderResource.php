<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use BackedEnum;
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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|UnitEnum|null $navigationGroup = '商城管理';

    protected static ?string $navigationLabel = '订单管理';

    protected static ?string $modelLabel = '订单';

    protected static ?string $pluralModelLabel = '订单';

    protected static ?int $navigationSort = 40;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('订单信息')
                    ->schema([
                        TextInput::make('order_no')->label('订单号')->disabled()->dehydrated(false),
                        TextInput::make('user_id')->label('用户ID')->disabled()->dehydrated(false),
                        TextInput::make('user.name')->label('用户昵称')->disabled()->dehydrated(false),
                        TextInput::make('product_name')->label('商品名称')->disabled()->dehydrated(false),
                        TextInput::make('product_price')->label('商品单价（分）')->disabled()->dehydrated(false),
                        TextInput::make('quantity')->label('购买数量')->disabled()->dehydrated(false),
                        TextInput::make('total_amount')
                            ->label('订单总金额（分）')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('pay_amount')
                            ->label('实付金额（分）')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('order_status')
                            ->label('订单状态')
                            ->options([
                                'pending_payment' => '待支付',
                                'paid' => '已支付',
                                'shipped' => '已发货',
                                'completed' => '已完成',
                                'cancelled' => '已取消',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('pay_status')
                            ->label('支付状态')
                            ->options(['unpaid' => '未支付', 'paid' => '已支付', 'refunded' => '已退款'])
                            ->required()
                            ->native(false),
                        Textarea::make('remark')->label('备注')->rows(3)->columnSpanFull(),
                        TextInput::make('logistics_company')->label('物流公司')->maxLength(64),
                        TextInput::make('logistics_no')->label('物流单号')->maxLength(64),
                        TextInput::make('paid_at')->label('支付时间')->disabled()->dehydrated(false),
                        TextInput::make('shipped_at')->label('发货时间')->disabled()->dehydrated(false),
                        TextInput::make('completed_at')->label('完成时间')->disabled()->dehydrated(false),
                        TextInput::make('cancelled_at')->label('取消时间')->disabled()->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make('收货地址')
                    ->schema([
                        TextInput::make('address.consignee_name')->label('收货人')->disabled()->dehydrated(false),
                        TextInput::make('address.consignee_phone')->label('联系电话')->disabled()->dehydrated(false),
                        TextInput::make('address.province')->label('省')->disabled()->dehydrated(false),
                        TextInput::make('address.city')->label('市')->disabled()->dehydrated(false),
                        TextInput::make('address.district')->label('区')->disabled()->dehydrated(false),
                        TextInput::make('address.detail_address')->label('详细地址')->disabled()->dehydrated(false),
                        TextInput::make('address.full_address')->label('完整地址')->disabled()->dehydrated(false)->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('order_no')->label('订单号')->searchable()->copyable(),
                TextColumn::make('user_id')
                    ->label('用户')
                    ->sortable(),
                TextColumn::make('product_name')->label('商品名称')->searchable()->limit(20),
                ImageColumn::make('product_image')->label('商品图片')->square()->imageSize(40),
                TextColumn::make('product_price')->label('单价')->formatStateUsing(fn (int $state): string => '¥'.number_format($state / 100, 2)),
                TextColumn::make('quantity')->label('数量')->numeric(),
                TextColumn::make('total_amount')->label('总额')->formatStateUsing(fn (int $state): string => '¥'.number_format($state / 100, 2)),
                TextColumn::make('order_status')->label('订单状态')->badge(),
                TextColumn::make('pay_status')->label('支付状态')->badge(),
                TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->recordUrl(fn (Order $record): string => static::getUrl('edit', ['record' => $record]));
    }

    /**
     * @return Builder<Order>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'address']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
