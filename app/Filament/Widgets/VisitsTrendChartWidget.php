<?php

namespace App\Filament\Widgets;

use App\Models\VisitHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class VisitsTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Visits Trend (Last 30 Days)';
    protected static ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Cache::remember('dashboard_visits_trend', 300, function () {
            $days = collect();
            $carwashData = collect();
            $motorwashData = collect();
            $coffeeshopData = collect();

            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $days->push(now()->subDays($i)->format('d M'));

                $carwashData->push(
                    VisitHistory::whereDate('visited_at', $date)
                        ->whereJsonContains('loyalty_types', 'carwash')
                        ->count()
                );

                $motorwashData->push(
                    VisitHistory::whereDate('visited_at', $date)
                        ->whereJsonContains('loyalty_types', 'motorwash')
                        ->count()
                );

                $coffeeshopData->push(
                    VisitHistory::whereDate('visited_at', $date)
                        ->whereJsonContains('loyalty_types', 'coffeeshop')
                        ->count()
                );
            }

            return [
                'labels' => $days->toArray(),
                'carwash' => $carwashData->toArray(),
                'motorwash' => $motorwashData->toArray(),
                'coffeeshop' => $coffeeshopData->toArray(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Car Wash',
                    'data' => $data['carwash'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Motor Wash',
                    'data' => $data['motorwash'],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Coffee Shop',
                    'data' => $data['coffeeshop'],
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
