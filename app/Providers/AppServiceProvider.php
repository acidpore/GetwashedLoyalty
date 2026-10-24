<?php

namespace App\Providers;

use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Filament\Http\Responses\Auth\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        // Default Filament menyertakan opsi 'all' yang me-render seluruh tabel tanpa batas.
        // Pada tabel sebesar customer dan visit history itu menghabiskan memory PHP.
        Table::configureUsing(fn (Table $table) => $table->paginationPageOptions([10, 25, 50]));
        Column::configureUsing(fn (Column $column) => $column->alignCenter());

        // Row actions render as link views and are shown icon-only, so their label becomes the hover tooltip.
        TableAction::configureUsing(fn (TableAction $action) => $action->tooltip(
            fn (TableAction $action) => $action->getView() === TableAction::LINK_VIEW ? $action->getLabel() : null
        ));

        // Same dotted name Filament gives panel widgets, e.g. app.filament.widgets.analytics.kpi-overview.
        foreach (\App\Filament\Pages\Analytics::WIDGETS as $widget) {
            \Livewire\Livewire::component(
                collect(explode('\\', $widget))->map(fn ($part) => \Illuminate\Support\Str::kebab($part))->implode('.'),
                $widget,
            );
        }
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('otp-request', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan. Silakan tunggu 1 menit.',
                ], 429);
            });
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan verifikasi. Silakan tunggu 1 menit.',
                ], 429);
            });
        });

        RateLimiter::for('checkin', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak check-in. Silakan tunggu 1 menit.',
                ], 429);
            });
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function () {
                return back()->withErrors(['email' => 'Terlalu banyak percobaan login. Silakan tunggu 1 menit.']);
            });
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
