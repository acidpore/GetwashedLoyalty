<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CarwashStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $threshold = SystemSetting::carwashRewardThreshold();
        $totalCustomers = Customer::where('carwash_total_visits', '>', 0)->count();
        $totalVisits = Customer::sum('carwash_total_visits');
        $readyForReward = Customer::where('carwash_points', '>=', $threshold)->count();

        return [
            Stat::make('Total Car Wash Customers', $totalCustomers)
                ->description('Unique customers')
                ->icon('heroicon-o-users'),
            
            Stat::make('Total Car Wash Visits', $totalVisits)
                ->description('All time check-ins')
                ->icon('heroicon-o-calendar'),
            
            Stat::make('Ready for Reward', $readyForReward)
                ->description("Customers with {$threshold}+ points")
                ->icon('heroicon-o-gift')
                ->color('success'),
        ];
    }
}
