<?php

namespace App\Filament\Resources\PaymentOrders;

use App\Filament\Resources\PaymentOrders\Pages\EditPaymentOrder;
use App\Filament\Resources\PaymentOrders\Pages\ListPaymentOrders;
use App\Models\PaymentOrder;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PaymentOrderResource extends Resource
{
    protected static ?string $model = PaymentOrder::class;

    protected static string|UnitEnum|null $navigationGroup = '财务管理';

    protected static ?string $navigationLabel = '账户流水';

    protected static ?string $modelLabel = '账户流水';

    protected static ?string $pluralModelLabel = '账户流水';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('流水详情')->schema([
                TextInput::make('order_no')->label('订单号')->disabled()->dehydrated(false),
                TextInput::make('user_id')->label('用户ID')->disabled()->dehydrated(false),
                TextInput::make('user.name')->label('用户昵称')->disabled()->dehydrated(false),
                TextInput::make('title')->label('标题')->disabled()->dehydrated(false),
                TextInput::make('business_type')->label('业务类型')->disabled()->dehydrated(false),
                TextInput::make('business_id')->label('业务ID')->disabled()->dehydrated(false),
                TextInput::make('amount_fen')->label('金额(分)')->disabled()->dehydrated(false),
                TextInput::make('status')->label('支付状态')->disabled()->dehydrated(false),
                TextInput::make('transaction_id')->label('微信交易号')->disabled()->dehydrated(false),
                TextInput::make('paid_at')->label('支付时间')->disabled()->dehydrated(false),
                TextInput::make('created_at')->label('创建时间')->disabled()->dehydrated(false),
            ])->columns(2),
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
                    ->searchable()
                    ->description(fn (PaymentOrder $record): string => (string) ($record->user?->name ?? '')),
                TextColumn::make('user.name')->label('用户昵称')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')->label('订单标题')->searchable()->limit(20),
                TextColumn::make('amount_fen')->label('金额')->formatStateUsing(fn (int $state): string => '¥'.number_format($state / 100, 2)),
                TextColumn::make('business_type')->label('业务类型')->badge(),
                TextColumn::make('status')->label('支付状态')->badge(),
                TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
                TextColumn::make('paid_at')->label('支付时间')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('支付状态')->options([
                    'pending' => 'pending',
                    'paid' => 'paid',
                    'closed' => 'closed',
                    'failed' => 'failed',
                    'refunded' => 'refunded',
                ]),
            ])
            ->recordUrl(fn (PaymentOrder $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentOrders::route('/'),
            'edit' => EditPaymentOrder::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<PaymentOrder>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }
}
