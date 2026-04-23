<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\AppRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $allRoleOptions = collect(AppRole::VALUES)
            ->mapWithKeys(fn (string $value) => [$value => AppRole::labelCn($value)])
            ->all();

        $editorIsSuper = AppRole::normalize(auth()->user()?->role ?? '') === 'super_admin';

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('昵称 / 姓名')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('邮箱（唯一账号标识）')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (?string $state): bool => filled($state) && is_string($state) && str_contains($state, '@wechat.local')),
                TextInput::make('phone')
                    ->label('手机号')
                    ->tel()
                    ->maxLength(32)
                    ->placeholder('可选'),
                TextInput::make('avatar_url')
                    ->label('头像地址')
                    ->url()
                    ->maxLength(2048)
                    ->placeholder('可选：服务端存储的公开头像地址'),
                TextInput::make('wechat_openid')
                    ->label('微信开放标识')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('wechat_unionid')
                    ->label('微信联合标识')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('role')
                    ->label('角色')
                    ->options(fn () => $editorIsSuper
                        ? $allRoleOptions
                        : collect($allRoleOptions)->except(['super_admin'])->all())
                    ->required()
                    ->native(false)
                    ->default('user')
                    ->disabled(function (?Model $record): bool {
                        $actor = auth()->user();
                        if (! $actor instanceof User) {
                            return true;
                        }

                        if ($record instanceof User) {
                            if ((int) $actor->id === (int) $record->id) {
                                return true;
                            }

                            return ! $actor->can('changeRole', $record);
                        }

                        return ! $actor->can('create', User::class);
                    }),
                Toggle::make('is_sponsor')
                    ->label('赞助用户')
                    ->default(false),
                DateTimePicker::make('sponsor_until')
                    ->label('赞助有效期至')
                    ->seconds(false)
                    ->native(false),
                Toggle::make('is_active')
                    ->label('账号启用')
                    ->default(true)
                    ->disabled(function (?Model $record): bool {
                        $actor = auth()->user();
                        if (! $actor instanceof User || ! $record instanceof User) {
                            return false;
                        }

                        if ((int) $actor->id === (int) $record->id) {
                            return true;
                        }

                        return ! $actor->can('update', $record);
                    })
                    ->hint(fn (?Model $record): ?string => $record instanceof User && (int) auth()->id() === (int) $record->id
                        ? '不能禁用自己的账号。'
                        : null),
                DateTimePicker::make('email_verified_at')
                    ->label('邮箱验证时间'),
                TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Section::make('饮食与推荐档案')
                    ->relationship('profile')
                    ->visibleOn('edit')
                    ->schema([
                        DatePicker::make('birthday')
                            ->label('生日')
                            ->native(false),
                        Select::make('gender')
                            ->label('性别（基础资料）')
                            ->options([
                                'unknown' => '未设置',
                                'male' => '男',
                                'female' => '女',
                                'undisclosed' => '不愿透露',
                            ])
                            ->native(false),
                        TextInput::make('height_cm')
                            ->label('身高（cm）')
                            ->numeric()
                            ->minValue(50)
                            ->maxValue(260),
                        TextInput::make('weight_kg')
                            ->label('体重（kg）')
                            ->numeric()
                            ->step(0.1),
                        TextInput::make('target_weight_kg')
                            ->label('目标体重（kg）')
                            ->numeric()
                            ->step(0.1),
                        TagsInput::make('flavor_preferences')
                            ->label('口味偏好')
                            ->placeholder('回车添加标签'),
                        TagsInput::make('taboo_ingredients')
                            ->label('忌口食材'),
                        TagsInput::make('dislike_ingredients')
                            ->label('不喜欢食材'),
                        TagsInput::make('allergy_ingredients')
                            ->label('过敏食材'),
                        TagsInput::make('diet_preferences')
                            ->label('饮食类型偏好'),
                        TagsInput::make('diet_goal')
                            ->label('饮食目标（问卷多选）'),
                        TagsInput::make('lifestyle_tags')
                            ->label('生活习惯标签'),
                        TextInput::make('cooking_frequency')
                            ->label('做饭频率（often/sometimes/rarely/takeout）')
                            ->maxLength(32),
                        TextInput::make('family_size')
                            ->label('用餐场景（single/couple/family3/family5）')
                            ->maxLength(32),
                        TextInput::make('health_goal')
                            ->label('饮食 / 健康目标')
                            ->maxLength(255),
                        TextInput::make('recommendation_style')
                            ->label('推荐风格')
                            ->maxLength(64)
                            ->placeholder('如：清淡优先'),
                        Toggle::make('destiny_mode_enabled')
                            ->label('食命推荐'),
                        Toggle::make('period_feature_enabled')
                            ->label('特殊时期贴心推荐（用户已授权）'),
                        Toggle::make('accepts_product_recommendation')
                            ->label('接受商品推荐'),
                        TextInput::make('onboarding_version')
                            ->label('问卷版本号')
                            ->numeric(),
                        DateTimePicker::make('onboarding_completed_at')
                            ->label('完成首次引导时间')
                            ->seconds(false),
                    ])
                    ->columns(2),
            ]);
    }
}
