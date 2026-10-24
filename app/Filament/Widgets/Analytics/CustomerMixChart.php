<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Widgets\ChartWidget;

class CustomerMixChart extends ChartWidget
{
    use TerminalChart;

    protected static ?string $heading = 'Customer baru vs kembali';

    protected static ?string $maxHeight = '280px';

    public function getDescription(): ?string
    {
        return 'Customer aktif per ' . $this->report()['trend']['unit'] . '. Bisnis sehat ditopang customer yang kembali.';
    }

    protected function getData(): array
    {
        $rows = $this->report()['trend']['rows'];

        return [
            'labels' => array_column($rows, 'label'),
            'datasets' => [
                $this->bar(['label' => 'Kembali', 'data' => array_column($rows, 'returning'), 'backgroundColor' => '#4f46e5']),
                $this->bar(['label' => 'Baru', 'data' => array_column($rows, 'new'), 'backgroundColor' => '#a5b4fc']),
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return $this->terminalOptions(stacked: true);
    }
}
