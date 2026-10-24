<?php

namespace App\Filament\Widgets\Analytics;

class InsightsWidget extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.insights';

    protected int|string|array $columnSpan = 'full';
}
