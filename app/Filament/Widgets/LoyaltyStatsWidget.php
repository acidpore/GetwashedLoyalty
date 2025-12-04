<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\QrCode;
use App\Models\VisitHistory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoyaltyStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalCustomers = Customer::count();
        $customersThisMonth = Customer::whereMonth('created_at', now()->month)->count();
        
        $visitsToday = VisitHistory::whereDate('visited_at', today())->count();
        $visitsThisWeek = VisitHistory::whereBetween('visited_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        $totalCarwashVisits = Customer::sum('carwash_total_visits');
        $totalCoffeeshopVisits = Customer::sum('coffeeshop_total_visits');
        $totalVisits = $totalCarwashVisits + $totalCoffeeshopVisits;
        
        $totalQrCodes = QrCode::where('is_active', true)->count();
        
        return [
            Stat::make('Total Customers', $totalCustomers)
                ->description("{$customersThisMonth} new this month")
                ->icon('heroicon-o-users')
                ->color('success'),
            
            Stat::make('Total Visits', $totalVisits)
                ->description("{$visitsToday} today, {$visitsThisWeek} this week")
                ->icon('heroicon-o-calendar')
                ->color('info'),
            
            Stat::make('Active QR Codes', $totalQrCodes)
                ->description('Permanent and one-time codes')
                ->icon('heroicon-o-qr-code')
                ->color('warning'),
        ];
    }
}
