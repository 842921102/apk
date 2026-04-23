<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Models\UserDailyStatus;
use App\Support\UserDailyStatusMvp;
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
                        TextEntry::make('id')->label('用户编号')->copyable(),
                        ImageEntry::make('avatar_url')
                            ->label('头像')
                            ->circular()
                            ->defaultImageUrl('data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#d1d5db"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6v1H4v-1z"/></svg>')),
                        TextEntry::make('name')
                            ->label('昵称 / 姓名')
                            ,
                        TextEntry::make('avatar_url')
                            ->label('头像地址')
                            ->placeholder('未在服务端存储（小程序选择头像多为本地临时路径，未回传时可空）'),
                        TextEntry::make('phone')
                            ->label('手机号')
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label('邮箱（账号标识）')
                            ->copyable(),
                        TextEntry::make('profile.gender')
                            ->label('性别（基础资料）')
                            ->formatStateUsing(function (?string $state): string {
                                return match ((string) $state) {
                                    'male' => '男',
                                    'female' => '女',
                                    'undisclosed' => '不愿透露',
                                    default => '未设置',
                                };
                            })
                            ->placeholder('—'),
                        TextEntry::make('profile.birthday')
                            ->label('生日')
                            ->date()
                            ->placeholder('—'),
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
                Section::make('饮食偏好')
                    ->schema([
                        TextEntry::make('profile.flavor_preferences')
                            ->label('口味偏好')
                            ->formatStateUsing(fn ($state): string => self::formatTagList($state)),
                        TextEntry::make('profile.taboo_ingredients')
                            ->label('忌口')
                            ->formatStateUsing(fn ($state): string => self::formatTagList($state)),
                        TextEntry::make('profile.diet_preferences')
                            ->label('饮食类型')
                            ->formatStateUsing(fn ($state): string => self::formatTagList($state)),
                        TextEntry::make('profile.health_goal')
                            ->label('饮食 / 健康目标')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('推荐设置')
                    ->schema([
                        TextEntry::make('profile.recommendation_style')
                            ->label('推荐风格')
                            ->placeholder('—'),
                        TextEntry::make('profile.destiny_mode_enabled')
                            ->label('食命推荐')
                            ->formatStateUsing(fn (?bool $state): string => $state ? '已开启' : '关闭'),
                        TextEntry::make('profile.period_feature_enabled')
                            ->label('特殊时期贴心推荐（用户授权）')
                            ->formatStateUsing(fn (?bool $state): string => $state ? '已开启' : '关闭'),
                        TextEntry::make('profile.onboarding_completed_at')
                            ->label('完成首次引导')
                            ->dateTime()
                            ->placeholder('未完成'),
                        TextEntry::make('profile.onboarding_version')
                            ->label('问卷版本')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('微信信息')
                    ->schema([
                        TextEntry::make('wechat_login_type')
                            ->label('登录方式')
                            ->state('微信登录')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('wechat_openid')->label('开放标识')->copyable(),
                        TextEntry::make('wechat_unionid')->label('联合标识')->copyable()->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('最近每日状态')
                    ->schema([
                        TextEntry::make('daily_status_digest')
                            ->label('近 7 日')
                            ->columnSpanFull()
                            ->state(fn (User $record): string => self::formatRecentDaily($record)),
                    ])
                    ->columns(1)
                    ->collapsible(),
                Section::make('最近推荐记录')
                    ->schema([
                        TextEntry::make('recent_histories_digest')
                            ->label('近 10 条')
                            ->columnSpanFull()
                            ->state(fn (User $record): string => self::formatRecentHistories($record)),
                    ])
                    ->columns(1)
                    ->collapsible(),
                Section::make('业务数据概览')
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

    /**
     * @param  mixed  $state
     */
    private static function formatTagList($state): string
    {
        if (! is_array($state) || $state === []) {
            return '—';
        }

        return implode('、', array_map(strval(...), $state));
    }

    private static function formatRecentDaily(User $user): string
    {
        $rows = $user->dailyStatuses()
            ->orderByDesc('status_date')
            ->limit(7)
            ->get();
        if ($rows->isEmpty()) {
            return '暂无';
        }

        return $rows->map(function (UserDailyStatus $row): string {
            $d = $row->status_date->format('Y-m-d');
            $flavorStr = UserDailyStatusMvp::flavorTagsToPreferenceString($row->flavor_tags ?? []);
            $tabooStr = UserDailyStatusMvp::tabooTagsToPreferenceString($row->taboo_tags ?? []);
            $tabooNone = is_array($row->taboo_tags) && in_array('none', $row->taboo_tags, true);

            $parts = array_filter([
                $row->mood ? '心情 '.$row->mood : null,
                $row->wanted_food_style ? '想吃的风格 '.$row->wanted_food_style : null,
                $row->body_state ? '身体 '.$row->body_state : null,
                $flavorStr !== '' ? '口味 '.$flavorStr : null,
                $tabooStr !== '' ? '忌口 '.$tabooStr : ($tabooNone ? '忌口 暂无' : null),
                $row->period_status && $row->period_status !== 'none'
                    ? '特殊时期自报：'.self::periodLabel((string) $row->period_status)
                    : null,
            ]);

            return $d.' · '.(implode('，', $parts) !== '' ? implode('，', $parts) : '（无明细）');
        })->join("\n");
    }

    private static function periodLabel(string $code): string
    {
        return match ($code) {
            'menstrual' => '经期',
            'premenstrual' => '经前期',
            'postmenstrual' => '经后期',
            'unknown' => '不确定',
            default => $code,
        };
    }

    private static function formatRecentHistories(User $user): string
    {
        $rows = $user->histories()->latest()->limit(10)->get();
        if ($rows->isEmpty()) {
            return '暂无';
        }

        return $rows->map(function ($h): string {
            $t = (string) $h->title;
            $at = $h->created_at?->format('Y-m-d H:i') ?? '';

            return $t.' · '.$at;
        })->join("\n");
    }
}
