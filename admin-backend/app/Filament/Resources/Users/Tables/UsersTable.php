<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Support\AdminActionLogger;
use App\Support\AppRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $roleOptions = collect(AppRole::VALUES)
            ->mapWithKeys(fn (string $value) => [$value => AppRole::labelCn($value)])
            ->all();

        return $table
            ->deferFilters(false)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('avatar_url')
                    ->label('头像')
                    ->circular()
                    ->imageHeight(40)
                    ->imageWidth(40)
                    ->toggleable()
                    ->defaultImageUrl(fn (): string => 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#d1d5db"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6v1H4v-1z"/></svg>')),
                TextColumn::make('name')
                    ->label('昵称 / 姓名')
                    ->description(fn (User $record): string => str_contains((string) $record->email, '@wechat.local') ? '微信登录' : '后台账号')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('phone')
                    ->label('手机号')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('wechat_openid')
                    ->label('OpenID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(10)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('wechat_unionid')
                    ->label('UnionID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—')
                    ->limit(10)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('role')
                    ->label('角色')
                    ->formatStateUsing(fn (?string $state): string => AppRole::labelCn((string) $state))
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('状态')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('最近登录')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('favorites_count')
                    ->label('收藏数')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('histories_count')
                    ->label('历史数')
                    ->badge()
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('user_id')
                    ->label('用户 ID')
                    ->schema([
                        TextInput::make('id')
                            ->numeric()
                            ->label('精确 ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['id'] ?? null),
                            fn (Builder $q): Builder => $q->where('users.id', (int) $data['id']),
                        );
                    }),
                Filter::make('phone')
                    ->label('手机号')
                    ->schema([
                        TextInput::make('value')
                            ->label('包含匹配')
                            ->placeholder('后四位或完整号码'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $v = isset($data['value']) ? trim((string) $data['value']) : '';

                        return $query->when(
                            $v !== '',
                            fn (Builder $q): Builder => $q->where('phone', 'like', '%'.$v.'%'),
                        );
                    }),
                SelectFilter::make('role')
                    ->label('角色')
                    ->options($roleOptions)
                    ->attribute('role'),
                TernaryFilter::make('is_active')
                    ->label('账号状态')
                    ->attribute('is_active')
                    ->boolean()
                    ->trueLabel('正常')
                    ->falseLabel('已禁用'),
                Filter::make('created_at')
                    ->label('注册时间')
                    ->schema([
                        DatePicker::make('from')->label('从'),
                        DatePicker::make('until')->label('至'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $q) => $q->whereDate('created_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $q) => $q->whereDate('created_at', '<=', $data['until']),
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth('6xl'),
                EditAction::make()
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false),
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? '禁用' : '启用')
                    ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? '确认禁用该用户？' : '确认启用该用户？')
                    ->modalDescription('禁用后该账号将无法使用小程序微信登录态（若后续接入服务端校验）；当前仅记录状态字段。')
                    ->visible(fn (User $record): bool => auth()->user()?->can('toggleActive', $record) ?? false)
                    ->action(function (User $record): void {
                        if (! auth()->user()?->can('toggleActive', $record)) {
                            return;
                        }

                        $before = (bool) $record->is_active;
                        $record->is_active = ! $record->is_active;
                        $record->save();

                        AdminActionLogger::record('user.toggle_active', $record, [
                            'before' => $before,
                            'after' => (bool) $record->is_active,
                        ]);
                    }),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
