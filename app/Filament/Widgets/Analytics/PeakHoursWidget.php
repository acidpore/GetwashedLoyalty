<?php

namespace App\Filament\Widgets\Analytics;

class PeakHoursWidget extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.peak-hours';

    protected int|string|array $columnSpan = 'full';
}
