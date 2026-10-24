<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\BusinessAnalytics;
use Filament\Widgets\ChartWidget;

class ProgramTrendChart extends ChartWidget
{
    use TerminalChart;

    protected static ?string $heading = 'Kunjungan per program';

    protected static ?string $maxHeight = '280px';

    public function getDescription(): ?string
    {
        return 'Komposisi check-in per ' . $this->report()['trend']['unit'] . '. Satu kunjungan cuci + kopi dihitung di dua program.';
    }

    protected function getData(): array
    {
        $trend = $this->report()['trend'];

        return [
            'labels' => array_column($trend['rows'], 'label'),
            'datasets' => array_map(fn ($p) => $this->bar([
                'label' => BusinessAnalytics::PROGRAMS[$p],
                'data' => array_map(fn ($row) => $row['programs'][$p], $trend['rows']),
                'backgroundColor' => BusinessAnalytics::COLORS[$p],
            ]), $trend['programs']),
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
