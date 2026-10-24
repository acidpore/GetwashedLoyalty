<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\ChartWidget;

class VisitsTrendChart extends ChartWidget
{
    use TerminalChart;

    protected static ?string $heading = 'Kunjungan vs periode sebelumnya';

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    public function getDescription(): ?string
    {
        $r = $this->report();

        return "Check-in per {$r['trend']['unit']}. Garis putus-putus: {$r['prev_range']}.";
    }

    protected function getData(): array
    {
        $rows = $this->report()['trend']['rows'];

        return [
            'labels' => array_column($rows, 'label'),
            'datasets' => [
                [
                    'label' => 'Periode ini',
                    'data' => array_column($rows, 'total'),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99,102,241,.10)',
                    'fill' => 'origin',
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                    'pointHoverBorderWidth' => 2,
                    'pointHoverBorderColor' => '#fff',
                    'tension' => 0.25,
                ],
                [
                    'label' => 'Periode sebelumnya',
                    'data' => array_column($rows, 'previous'),
                    'borderColor' => 'rgba(128,128,128,.7)',
                    'backgroundColor' => 'rgba(128,128,128,.7)',
                    'borderDash' => [4, 4],
                    'borderWidth' => 1.5,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 4,
                    'tension' => 0.25,
                    'fill' => false,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return $this->terminalOptions();
    }
}
