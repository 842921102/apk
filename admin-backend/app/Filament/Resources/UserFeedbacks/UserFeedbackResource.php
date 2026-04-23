<?php

namespace App\Filament\Resources\UserFeedbacks;

use App\Filament\Resources\UserFeedbacks\Pages\EditUserFeedback;
use App\Filament\Resources\UserFeedbacks\Pages\ListUserFeedbacks;
use App\Models\UserFeedback;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class UserFeedbackResource extends Resource
{
    protected static ?string $model = UserFeedback::class;

    protected static ?string $navigationLabel = '用户反馈';

    protected static string|UnitEnum|null $navigationGroup = '内容管理';

    protected static ?string $modelLabel = '需求反馈';

    protected static ?string $pluralModelLabel = '需求反馈';

    protected static ?int $navigationSort = 50;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('反馈内容')
                ->schema([
                    TextInput::make('id')->label('编号')->disabled(),
                    TextInput::make('user_id')->label('用户编号')->disabled(),
                    TextInput::make('title')->label('标题')->disabled(),
                    TextInput::make('contact')->label('联系方式')->disabled()->placeholder('—'),
                    Textarea::make('content')->label('反馈详情')->rows(8)->disabled()->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('处理信息')
                ->schema([
                    Select::make('status')
                        ->label('处理状态')
                        ->required()
                        ->options([
                            'pending' => '待处理',
                            'processing' => '处理中',
                            'resolved' => '已解决',
                            'rejected' => '暂不采纳',
                        ]),
                    DateTimePicker::make('handled_at')->label('处理时间')->seconds(false),
                    Textarea::make('admin_remark')->label('后台备注')->rows(5)->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->columns([
                TextColumn::make('id')->label('编号')->sortable()->copyable(),
                TextColumn::make('title')->label('标题')->searchable()->limit(26)->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('content')->label('反馈内容')->limit(32)->toggleable(),
                TextColumn::make('user.name')
                    ->label('用户')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('contact')->label('联系方式')->placeholder('—')->toggleable(),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'processing' => '处理中',
                        'resolved' => '已解决',
                        'rejected' => '暂不采纳',
                        default => '待处理',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'processing' => 'warning',
                        'resolved' => 'success',
                        'rejected' => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('created_at')->label('提交时间')->dateTime()->sortable(),
                TextColumn::make('handled_at')->label('处理时间')->dateTime()->placeholder('—')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('处理状态')
                    ->options([
                        'pending' => '待处理',
                        'processing' => '处理中',
                        'resolved' => '已解决',
                        'rejected' => '暂不采纳',
                    ]),
            ])
            ->recordUrl(fn (UserFeedback $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserFeedbacks::route('/'),
            'edit' => EditUserFeedback::route('/{record}/edit'),
        ];
    }
}
