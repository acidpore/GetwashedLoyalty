<?php

namespace App\Filament\Widgets\Analytics;

class RetentionWidget extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.retention';

    protected int|string|array $columnSpan = 1;
}
