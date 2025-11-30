<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'System Settings';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?int $reward_points_threshold = null;

    public function mount(): void
    {
        $this->reward_points_threshold = SystemSetting::rewardPointsThreshold();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Current Configuration')
                    ->description('View current active settings')
                    ->schema([
                        Placeholder::make('current_threshold')
                            ->label('Current Active Reward Threshold')
                            ->content(fn () => SystemSetting::rewardPointsThreshold() . ' points')
                            ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600']),
                    ])
                    ->collapsible()
                    ->compact(),
                    
                Section::make('Loyalty Program Settings')
                    ->description('Configure reward and points settings')
                    ->schema([
                        TextInput::make('reward_points_threshold')
                            ->label('Reward Points Threshold')
                            ->helperText('Number of points customers need to earn a reward')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required()
                            ->suffix('points')
                            ->default(5),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::set(
            'reward_points_threshold',
            $data['reward_points_threshold'],
            'Number of points required to earn a reward'
        );

        $this->reward_points_threshold = $data['reward_points_threshold'];

        Notification::make()
            ->title('Settings saved successfully')
            ->body('Reward threshold updated to ' . $data['reward_points_threshold'] . ' points')
            ->success()
            ->send();
    }
}
