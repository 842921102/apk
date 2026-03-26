<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\AppRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $roleOptions = collect(AppRole::VALUES)
            ->mapWithKeys(fn (string $value) => [$value => AppRole::labelCn($value)])
            ->all();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('wechat_openid')
                    ->label('微信 OpenID')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('wechat_unionid')
                    ->label('微信 UnionID')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('role')
                    ->label('角色')
                    ->options($roleOptions)
                    ->required()
                    ->native(false)
                    ->default('user'),
                DateTimePicker::make('email_verified_at')
                    ->label('邮箱验证时间'),
                TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->helperText('新建用户必填。编辑用户时留空则不修改密码。')
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
            ]);
    }
}
