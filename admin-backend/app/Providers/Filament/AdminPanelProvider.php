<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\AdminLogin;
use App\Filament\Pages\WorkbenchDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->homeUrl(fn (): string => WorkbenchDashboard::getUrl(panel: 'admin'))
            ->login(AdminLogin::class)
            ->brandName('后台管理')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('用户管理')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('内容管理')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('商城管理')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('财务管理')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('数据管理')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('系统管理')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('系统配置')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                WorkbenchDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
