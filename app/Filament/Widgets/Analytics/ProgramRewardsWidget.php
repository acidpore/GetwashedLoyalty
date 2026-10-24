<?php

namespace App\Filament\Widgets\Analytics;

class ProgramRewardsWidget extends AnalyticsWidget
{
    protected static string $view = 'filament.analytics.program-rewards';

    protected int|string|array $columnSpan = 1;
}
