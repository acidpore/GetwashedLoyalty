<?php

namespace App\Providers\Filament;

use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** The Latte back office + till, open to admins and cashiers. */
class KasirPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $domain = config('app.kasir_domain');

        return $panel
            ->id('kasir')
            ->domain($domain)
            ->path($domain ? '' : 'kasir')
            ->login()
            ->brandName('Getwashed Latte')
            ->brandLogo(fn () => view('filament.kasir.brand'))
            ->favicon(asset('Logo Get Washed Updated.png'))
            ->colors(['primary' => Color::Amber])
            ->font('Inter')
            ->defaultThemeMode(ThemeMode::System)
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Utama'),
                NavigationGroup::make('Transaksi')->collapsible(),
                NavigationGroup::make('Produk')->collapsible(),
                NavigationGroup::make('Pengaturan')->collapsible(),
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Admin Loyalty')
                    ->group('Utama')
                    ->sort(99)
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url('/admin')
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->pages([
                \App\Filament\Kasir\Pages\LatteDashboard::class,
                \App\Filament\Kasir\Pages\Pos::class,
                \App\Filament\Kasir\Pages\Laporan::class,
            ])
            ->resources([
                \App\Filament\Resources\Latte\OrderResource::class,
                \App\Filament\Resources\Latte\ProductResource::class,
                \App\Filament\Kasir\Resources\IngredientResource::class,
                \App\Filament\Kasir\Resources\PromoResource::class,
                \App\Filament\Kasir\Resources\CashierResource::class,
                \App\Filament\Kasir\Resources\CashShiftResource::class,
            ])
            ->renderHook(PanelsRenderHook::HEAD_START, fn () => new HtmlString('<script>// Phones start with the sidebar shut; Filament otherwise persists it open from the last visit.
if (matchMedia("(max-width: 1023px)").matches) localStorage.setItem("isOpen", "false")</script>'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString('<link rel="manifest" href="/kasir.webmanifest"><link rel="apple-touch-icon" href="/img/icon-180.png"><meta name="theme-color" content="#18181b"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="Getwashed">'))
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => new HtmlString('<link rel="stylesheet" href="' . asset('css/pos.css') . '?v=17">'))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => Blade::render('<div class="lt-topbar-theme"><x-filament-panels::theme-switcher /></div>'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.kasir.sidebar-user'))
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
            ->authMiddleware([Authenticate::class]);
    }
}
