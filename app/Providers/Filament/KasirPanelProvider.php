<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** The Latte till: one full-screen page, no sidebar, open to admins and cashiers. */
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
            ->brandName('Getwashed Latte · Kasir')
            ->favicon(asset('Logo Get Washed Updated.png'))
            ->colors(['primary' => Color::Amber])
            ->navigation(false)
            ->topbar(false)
            ->pages([\App\Filament\Kasir\Pages\Pos::class])
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => new HtmlString('<link rel="stylesheet" href="' . asset('css/pos.css') . '?v=1">'))
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
