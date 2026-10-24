<?php

namespace App\Filament\Widgets\Analytics;

class TopCustomersWidget extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.top-customers';

    protected int|string|array $columnSpan = 1;
}
