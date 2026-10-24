<?php

namespace App\Filament\Widgets\Analytics;

class SegmentsWidget extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.segments';

    protected int|string|array $columnSpan = 1;
}
