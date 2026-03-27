<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\AppRole;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基础资料')
                    ->schema([
                        TextEntry::make('id')->label('用户 ID'),
                        ImageEntry::make('avatar_url')
                            ->label('头像')
                            ->circular()
                            ->defaultImageUrl('data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#d1d5db"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6v1H4v-1z"/></svg>')),
                        TextEntry::make('name')
                            ->label('昵称 / 姓名')
                            ->helperText('微信登录用户默认可能为「微信用户」，可在管理端修正展示名；小程序内自定义头像昵称若未同步接口则仅本字段可见。'),
                        TextEntry::make('avatar_url')
                            ->label('头像 URL')
                            ->placeholder('未在服务端存储（小程序 chooseAvatar 多为本地临时路径，未回传时可空）'),
                        TextEntry::make('phone')
                            ->label('手机号')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('邮箱（账号标识）'),
                        TextEntry::make('role')
                            ->label('角色')
                            ->formatStateUsing(fn (?string $state): string => AppRole::labelCn((string) $state))
                            ->badge(),
                        TextEntry::make('is_active')
                            ->label('状态')
                            ->formatStateUsing(fn (?bool $state): string => $state ? '正常' : '已禁用')
                            ->badge()
                            ->color(fn (?bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('created_at')->label('注册时间')->dateTime(),
                        TextEntry::make('last_login_at')
                            ->label('最近登录（小程序微信登录）')
                            ->dateTime()
                            ->placeholder('尚无记录'),
                    ])
                    ->columns(2),
                Section::make('微信信息')
                    ->schema([
                        TextEntry::make('wechat_login_type')
                            ->label('登录方式')
                            ->state('微信登录')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('wechat_openid')->label('OpenID')->copyable(),
                        TextEntry::make('wechat_unionid')->label('UnionID')->copyable()->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('业务数据概览')
                    ->description('收藏已写入本库 `favorites` 表；历史记录已写入本库 `recipe_histories` 表。')
                    ->schema([
                        TextEntry::make('favorites_count')
                            ->label('收藏数量')
                            ->state(fn (User $record): int => (int) ($record->favorites_count ?? 0))
                            ->badge()
                            ->color('warning')
                            ->url(fn (User $record): string => '/admin/favorites?tableFilters[user_id][id]='.$record->id),
                        TextEntry::make('histories_count')
                            ->label('历史数量')
                            ->state(fn (User $record): int => (int) ($record->histories_count ?? 0))
                            ->badge()
                            ->color('gray')
                            ->url(fn (User $record): string => '/admin/histories?tableFilters[user_id][id]='.$record->id),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('操作区')
                    ->schema([
                        TextEntry::make('action_edit')
                            ->label('修改角色 / 资料')
                            ->state('进入用户编辑页')
                            ->badge()
                            ->color('primary')
                            ->url(fn (User $record): string => '/admin/users/'.$record->id.'/edit'),
                        TextEntry::make('action_favorites')
                            ->label('查看收藏记录')
                            ->state('打开该用户收藏列表')
                            ->url(fn (User $record): string => '/admin/favorites?tableFilters[user_id][id]='.$record->id),
                        TextEntry::make('action_histories')
                            ->label('查看历史记录')
                            ->state('打开该用户历史列表')
                            ->url(fn (User $record): string => '/admin/histories?tableFilters[user_id][id]='.$record->id),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }
}
