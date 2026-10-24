<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\BusinessAnalytics;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Shared look for the analytics charts: hairline grid, mono ticks, dark tooltip. */
trait TerminalChart
{
    use InteractsWithPageFilters;

    protected function getPollingInterval(): ?string
    {
        return null;
    }

    protected function report(): array
    {
        return BusinessAnalytics::report($this->filters);
    }

    protected function terminalOptions(bool $stacked = false): array
    {
        $mono = ['family' => "'JetBrains Mono', ui-monospace, monospace", 'size' => 10];
        $grid = ['color' => 'rgba(128,128,128,.14)', 'drawTicks' => false];

        return [
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'align' => 'start',
                    'labels' => ['usePointStyle' => true, 'pointStyle' => 'rectRounded', 'boxWidth' => 8, 'boxHeight' => 8, 'padding' => 14, 'font' => ['size' => 11, 'weight' => '600']],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(9,9,11,.94)',
                    'borderColor' => 'rgba(255,255,255,.12)',
                    'borderWidth' => 1,
                    'cornerRadius' => 4,
                    'padding' => 10,
                    'boxPadding' => 4,
                    'usePointStyle' => true,
                    'titleFont' => [...$mono, 'size' => 11, 'weight' => '700'],
                    'bodyFont' => [...$mono, 'size' => 11],
                ],
            ],
            'scales' => [
                'x' => ['stacked' => $stacked, 'grid' => ['display' => false], 'border' => ['display' => false], 'ticks' => ['font' => $mono, 'maxRotation' => 0, 'autoSkipPadding' => 16, 'padding' => 6]],
                'y' => ['stacked' => $stacked, 'beginAtZero' => true, 'grid' => $grid, 'border' => ['display' => false, 'dash' => [3, 3]], 'ticks' => ['font' => $mono, 'precision' => 0, 'padding' => 8, 'maxTicksLimit' => 6]],
            ],
        ];
    }

    protected function bar(array $extra): array
    {
        // 2px surface gap between stacked segments comes from the border.
        return ['borderRadius' => 3, 'borderSkipped' => false, 'borderWidth' => 1, 'borderColor' => 'rgba(0,0,0,0)', 'maxBarThickness' => 26, ...$extra];
    }
}
