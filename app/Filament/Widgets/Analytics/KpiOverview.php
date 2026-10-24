<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\BusinessAnalytics;

class KpiOverview extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.kpis';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $r = BusinessAnalytics::report($this->filters);
        $k = $r['kpi'];
        $rows = $r['trend']['rows'];
        $active = fn ($row) => $row['new'] + $row['returning'];

        return ['r' => $r, 'tiles' => [
            $this->tile('Kunjungan', $k['visits'], $r, 'Total check-in', array_column($rows, 'total')),
            $this->tile('Customer aktif', $k['active'], $r, 'Customer berbeda yang datang', array_map($active, $rows)),
            $this->tile('Customer baru', $k['new'], $r, 'Datang untuk pertama kali', array_column($rows, 'new')),
            $this->tile('Customer kembali', $k['returning_rate'], $r, 'Customer aktif yang pernah datang sebelumnya',
                array_map(fn ($row) => $active($row) ? $row['returning'] / $active($row) * 100 : 0, $rows), unit: '%'),
            $this->tile('Kunjungan / customer', $k['avg_visits'], $r, 'Rata-rata kunjungan tiap customer aktif',
                array_map(fn ($row) => $active($row) ? $row['total'] / $active($row) : 0, $rows), decimals: 2),
            $this->tile('Hadiah diklaim', $k['claims'], $r, 'Reward yang ditukar customer', array_column($rows, 'claims')),
        ]];
    }

    private function tile(string $label, array $pair, array $r, string $hint, array $spark, string $unit = '', int $decimals = 0): array
    {
        [$now, $before] = $pair;
        $format = fn ($n) => number_format($n, $decimals, ',', '.') . $unit;

        // Percentages compare in points; counts compare relative to the old value.
        $delta = ! $r['has_previous'] ? null
            : ($unit === '%' ? $now - $before : ($before ? ($now - $before) / $before * 100 : null));

        return [
            'label' => $label,
            'hint' => $hint,
            'value' => $format($now),
            'previous' => $r['has_previous'] ? $format($before) : null,
            'delta' => $delta === null ? null : number_format(abs($delta), 1, ',', '.') . ($unit === '%' ? ' pt' : '%'),
            'tone' => $delta === null || abs($delta) < 0.05 ? 'flat' : ($delta > 0 ? 'up' : 'down'),
            'spark' => $this->sparkline($spark),
        ];
    }

    /** SVG polyline points in a 100×28 box. */
    private function sparkline(array $values): ?string
    {
        if (count($values) < 2) {
            return null;
        }

        $min = min($values);
        $span = max($values) - $min ?: 1;
        $step = 100 / (count($values) - 1);

        return collect($values)->map(fn ($v, $i) => round($i * $step, 2) . ',' . round(26 - ($v - $min) / $span * 24, 2))->implode(' ');
    }
}
