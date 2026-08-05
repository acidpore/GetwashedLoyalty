<?php

namespace App\Filament\Widgets;

use App\Models\VisitHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class VisitsTrendChartWidget extends ChartWidget
{
    private const DAYS = 30;

    protected static ?string $heading = 'Visits Trend (Last 30 Days)';
    protected static ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Cache::remember('dashboard_visits_trend', 300, function () {
            $labels = [];
            $tally = [];

            for ($i = self::DAYS - 1; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $labels[] = $day->format('d M');
                $tally[$day->format('Y-m-d')] = ['carwash' => 0, 'motorwash' => 0, 'coffeeshop' => 0];
            }

            // One indexed range scan, tallied in PHP. The old per-day
            // whereDate + whereJsonContains ran 90 unindexed table scans.
            VisitHistory::query()
                ->where('visited_at', '>=', now()->subDays(self::DAYS - 1)->startOfDay())
                ->get(['visited_at', 'loyalty_types'])
                ->each(function (VisitHistory $visit) use (&$tally) {
                    $date = $visit->visited_at?->format('Y-m-d');

                    foreach ((array) $visit->loyalty_types as $type) {
                        if (isset($tally[$date][$type])) {
                            $tally[$date][$type]++;
                        }
                    }
                });

            return [
                'labels' => $labels,
                'carwash' => array_column($tally, 'carwash'),
                'motorwash' => array_column($tally, 'motorwash'),
                'coffeeshop' => array_column($tally, 'coffeeshop'),
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
