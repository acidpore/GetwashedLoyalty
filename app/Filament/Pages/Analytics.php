<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics as W;
use App\Services\BusinessAnalytics;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Analytics extends Dashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'analytics';

    protected static ?string $title = 'Analitik Bisnis';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.analytics';

    public function filtersForm(Form $form): Form
    {
        return $form->columns(['default' => 1, 'sm' => 2, 'md' => 2, 'lg' => 2, 'xl' => 2, '2xl' => 2])->schema([
            Select::make('period')
                ->label('Periode')
                ->options(BusinessAnalytics::PERIODS)
                ->default(30)
                ->selectablePlaceholder(false),
            Select::make('program')
                ->label('Program')
                ->options(BusinessAnalytics::PROGRAMS)
                ->placeholder('Semua program'),
        ]);
    }

    /**
     * Not registered on the panel (that would put them on the main dashboard),
     * so AppServiceProvider registers them with Livewire for lazy loading.
     */
    public const WIDGETS = [
            W\KpiOverview::class,
            W\InsightsWidget::class,
            W\VisitsTrendChart::class,
            W\ProgramTrendChart::class,
            W\CustomerMixChart::class,
            W\PeakHoursWidget::class,
            W\SegmentsWidget::class,
            W\RetentionWidget::class,
            W\ProgramRewardsWidget::class,
            W\TopCustomersWidget::class,
    ];

    public function getWidgets(): array
    {
        return self::WIDGETS;
    }

    public function getColumns(): int|string|array
    {
        return ['md' => 2];
    }
}
