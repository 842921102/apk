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
                    ->label('编号')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('昵称')
                    ->searchable()
                    ->limit(16)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable()
                    ->limit(22)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('phone')
                    ->label('手机号')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('profile.gender')
                    ->label('性别')
                    ->formatStateUsing(function (?string $state): string {
                        return match ((string) $state) {
                            'male' => '男',
                            'female' => '女',
                            'undisclosed' => '不愿透露',
                            default => '未设置',
                        };
                    })
                    ->placeholder('—'),
                TextColumn::make('profile.birthday')
                    ->label('生日')
                    ->date()
                    ->placeholder('—'),
                TextColumn::make('profile.flavor_preferences')
                    ->label('口味偏好')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state) || $state === []) {
                            return '—';
                        }

                        return implode('、', array_slice(array_map(strval(...), $state), 0, 12));
                    }),
                TextColumn::make('profile.health_goal')
                    ->label('饮食目标')
                    ->placeholder('—')
                    ->limit(24)
                    ->tooltip(fn (?string $state): ?string => $state),
                IconColumn::make('profile.destiny_mode_enabled')
                    ->label('食命推荐')
                    ->boolean()
                    ->placeholder('—'),
                IconColumn::make('profile.period_feature_enabled')
                    ->label('特殊时期推荐')
                    ->boolean()
                    ->placeholder('—'),
                TextColumn::make('role')
                    ->label('角色')
                    ->formatStateUsing(fn (?string $state): string => AppRole::labelCn((string) $state))
                    ->badge()
                    ->color(fn (?string $state): string => match ((string) $state) {
                        'super_admin' => 'danger',
                        'operator' => 'warning',
                        'viewer' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_sponsor')
                    ->label('赞助')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedHeart)
                    ->falseIcon(Heroicon::OutlinedNoSymbol)
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('账号启用')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')
                    ->label('最近登录')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('favorites_count')
                    ->label('收藏数')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('histories_count')
                    ->label('历史数')
                    ->badge()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('avatar_url')
                    ->label('头像')
                    ->circular()
                    ->imageHeight(40)
                    ->imageWidth(40)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->defaultImageUrl(fn (): string => 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#d1d5db"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6v1H4v-1z"/></svg>')),
                TextColumn::make('wechat_openid')
                    ->label('开放标识')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(10)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('wechat_unionid')
                    ->label('联合标识')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—')
                    ->limit(10)
                    ->tooltip(fn (?string $state): ?string => $state),
            ])
            ->filters([
                Filter::make('user_id')
                    ->label('用户编号')
                    ->schema([
                        TextInput::make('id')
                            ->numeric()
                            ->label('精确编号'),
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
                TernaryFilter::make('is_sponsor')
                    ->label('赞助用户')
                    ->attribute('is_sponsor')
                    ->boolean()
                    ->trueLabel('是')
                    ->falseLabel('否'),
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
                    ->label('查看')
                    ->slideOver()
                    ->modalWidth('6xl'),
                EditAction::make()
                    ->label('编辑')
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
                    ->label('删除')
                    ->visible(fn (User $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
