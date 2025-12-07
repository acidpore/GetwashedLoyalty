<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\QrCode;
use App\Models\VisitHistory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class LoyaltyStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = Cache::remember('dashboard_loyalty_stats', 300, function () {
            return [
                'total_customers' => Customer::count(),
                'customers_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
                'visits_today' => VisitHistory::whereDate('visited_at', today())->count(),
                'visits_this_week' => VisitHistory::whereBetween('visited_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'total_visits' => Customer::sum('carwash_total_visits') + Customer::sum('coffeeshop_total_visits'),
                'total_qr_codes' => QrCode::where('is_active', true)->count(),
            ];
        });

        return [
            Stat::make('Total Customers', $stats['total_customers'])
                ->description("{$stats['customers_this_month']} new this month")
                ->icon('heroicon-o-users')
                ->color('success'),
            
            Stat::make('Total Visits', $stats['total_visits'])
                ->description("{$stats['visits_today']} today, {$stats['visits_this_week']} this week")
                ->icon('heroicon-o-calendar')
                ->color('info'),
            
            Stat::make('Active QR Codes', $stats['total_qr_codes'])
                ->description('Permanent and one-time codes')
                ->icon('heroicon-o-qr-code')
                ->color('warning'),
        ];
    }
}
