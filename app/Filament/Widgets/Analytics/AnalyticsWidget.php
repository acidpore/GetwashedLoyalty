<?php

namespace App\Filament\Widgets\Analytics;

use App\Services\BusinessAnalytics;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

/** A view widget on the Analytics page; the Blade view gets the shared report as $r. */
abstract class AnalyticsWidget extends Widget
{
    use InteractsWithPageFilters;

    protected function getViewData(): array
    {
        return ['r' => BusinessAnalytics::report($this->filters)];
    }
}
