<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\VisitHistory;
use App\Models\SystemSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoyaltyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCustomers = Customer::count();
        $customersThisMonth = Customer::whereMonth('created_at', now()->month)->count();
        
        $visitsToday = VisitHistory::whereDate('visited_at', today())->count();
        $visitsThisWeek = VisitHistory::whereBetween('visited_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        $totalPointsGiven = Customer::sum('total_visits');
        
        $threshold = SystemSetting::rewardPointsThreshold();
        $customersReadyForReward = Customer::where('current_points', '>=', $threshold)->count();

        $totalPoints = Customer::sum('current_points');
        $totalVisits = Customer::sum('total_visits');
        
        return [
            Stat::make('Total Customers', $totalCustomers)
                ->description('All registered customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            
            Stat::make('Visits Today', $visitsToday)
                ->description($visitsThisWeek . ' this week')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([5, 8, 12, 15, 18, 22, $visitsToday]),
            
            Stat::make('Ready for Reward', $customersReadyForReward)
                ->description("Customers with {$threshold}+ points")
                ->descriptionIcon('heroicon-m-gift')
                ->color('warning'),
        ];
    }
}
