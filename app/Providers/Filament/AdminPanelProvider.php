<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Enums\ThemeMode;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->login()
            ->brandName('Getwashed')
            ->favicon(asset('Logo Get Washed Updated.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->defaultThemeMode(ThemeMode::System)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => Blade::render('<div class="gw-topbar-theme"><x-filament-panels::theme-switcher /></div>'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.sidebar-logout'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => new HtmlString('<link rel="stylesheet" href="' . asset('css/analytics.css') . '?v=4"><style>'
                // The switcher lives in the topbar from tablet up; on phones the topbar is too tight, so it stays in the profile dropdown there.
                . '@media (max-width:639px){.gw-topbar-theme{display:none}}'
                . '@media (min-width:640px){.fi-user-menu .fi-dropdown-list:has(> .fi-theme-switcher){display:none}}'
                // Row actions show icons only; the label stays for screen readers and doubles as the tooltip.
                . '.fi-ta-actions-cell .fi-link > span{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}'
                . '.fi-sidebar-nav{scrollbar-width:none;scrollbar-gutter:auto!important}.fi-sidebar-nav::-webkit-scrollbar{display:none}'
                . '</style>'))
            ->navigationItems([
                NavigationItem::make('Buka Kasir')
                    ->group('Latte')
                    ->sort(45)
                    ->icon('heroicon-o-calculator')
                    ->url(fn () => \App\Filament\Kasir\Pages\Pos::getUrl(panel: 'kasir'))
                    ->openUrlInNewTab(),
            ])
            ->navigationGroups([
                NavigationGroup::make('Car Wash')->collapsible(),
                NavigationGroup::make('Motor Wash')->collapsible(),
                NavigationGroup::make('Latte')->collapsible(),
                NavigationGroup::make('Management')->collapsible(),
            ])
            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->resources([
                \App\Filament\Resources\CustomerResource::class,
                \App\Filament\Resources\Carwash\CarwashCustomerResource::class,
                \App\Filament\Resources\Motorwash\MotorwashCustomerResource::class,
                \App\Filament\Resources\Coffeeshop\CoffeeshopCustomerResource::class,
                \App\Filament\Resources\QrCodeResource::class,
                \App\Filament\Resources\VisitHistoryResource::class,
                \App\Filament\Resources\RewardClaimResource::class,
                \App\Filament\Resources\Latte\OrderResource::class,
                \App\Filament\Resources\Latte\ProductResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\Settings::class,
                \App\Filament\Pages\Analytics::class,
            ])
            ->widgets([
                \App\Filament\Widgets\VisitsTrendChartWidget::class,
                \App\Filament\Widgets\ReadyForRewardTableWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\CheckAdminRole::class,
            ]);
    }
}