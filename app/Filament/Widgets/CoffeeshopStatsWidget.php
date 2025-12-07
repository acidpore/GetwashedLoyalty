<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class CoffeeshopStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $threshold = SystemSetting::coffeeshopRewardThreshold();
        
        $stats = Cache::remember('dashboard_coffeeshop_stats', 300, function () use ($threshold) {
            return [
                'total_customers' => Customer::where('coffeeshop_total_visits', '>', 0)->count(),
                'total_visits' => Customer::sum('coffeeshop_total_visits'),
                'ready_for_reward' => Customer::where('coffeeshop_points', '>=', $threshold)->count(),
            ];
        });

        return [
            Stat::make('Total Coffee Shop Customers', $stats['total_customers'])
                ->description('Unique customers')
                ->icon('heroicon-o-users'),
            
            Stat::make('Total Coffee Shop Visits', $stats['total_visits'])
                ->description('All time check-ins')
                ->icon('heroicon-o-calendar'),
            
            Stat::make('Ready for Reward', $stats['ready_for_reward'])
                ->description("Customers with {$threshold}+ points")
                ->icon('heroicon-o-gift')
                ->color('success'),
        ];
    }
}
