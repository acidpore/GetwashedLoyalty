<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'carwash_reward_threshold' => SystemSetting::carwashRewardThreshold(),
            'coffeeshop_reward_threshold' => SystemSetting::coffeeshopRewardThreshold(),
            'carwash_reward_message' => SystemSetting::carwashRewardMessage(),
            'coffeeshop_reward_message' => SystemSetting::coffeeshopRewardMessage(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Current Configuration')
                    ->description('Overview of active loyalty program settings')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('current_carwash')
                                    ->label('Car Wash Threshold')
                                    ->content(fn () => SystemSetting::carwashRewardThreshold() . ' points')
                                    ->extraAttributes(['class' => 'text-xl font-bold text-blue-600']),
                                
                                Placeholder::make('current_coffeeshop')
                                    ->label('Coffee Shop Threshold')
                                    ->content(fn () => SystemSetting::coffeeshopRewardThreshold() . ' points')
                                    ->extraAttributes(['class' => 'text-xl font-bold text-amber-600']),
                            ]),
                    ])
                    ->collapsible()
                    ->compact(),
                    
                Section::make('Car Wash Loyalty')
                    ->description('Configure car wash loyalty program')
                    ->schema([
                        TextInput::make('carwash_reward_threshold')
                            ->label('Reward Points Threshold')
                            ->helperText('Number of points needed for car wash reward')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required()
                            ->suffix('points')
                            ->default(5),
                        
                        TextInput::make('carwash_reward_message')
                            ->label('Reward Message')
                            ->helperText('Text shown when reward is achieved')
                            ->maxLength(100)
                            ->required()
                            ->default('DISKON CAR WASH'),
                    ])
                    ->columns(2),
                
                Section::make('Coffee Shop Loyalty')
                    ->description('Configure coffee shop loyalty program')
                    ->schema([
                        TextInput::make('coffeeshop_reward_threshold')
                            ->label('Reward Points Threshold')
                            ->helperText('Number of points needed for coffee shop reward')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required()
                            ->suffix('points')
                            ->default(5),
                        
                        TextInput::make('coffeeshop_reward_message')
                            ->label('Reward Message')
                            ->helperText('Text shown when reward is achieved')
                            ->maxLength(100)
                            ->required()
                            ->default('GRATIS KOPI'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SystemSetting::set(
            'carwash_reward_threshold',
            $data['carwash_reward_threshold'],
            'Points required for car wash reward'
        );

        SystemSetting::set(
            'coffeeshop_reward_threshold',
            $data['coffeeshop_reward_threshold'],
            'Points required for coffee shop reward'
        );

        SystemSetting::set(
            'carwash_reward_message',
            $data['carwash_reward_message'],
            'Reward message for car wash loyalty'
        );

        SystemSetting::set(
            'coffeeshop_reward_message',
            $data['coffeeshop_reward_message'],
            'Reward message for coffee shop loyalty'
        );

        SystemSetting::clearCache();

        Notification::make()
            ->title('Settings saved successfully')
            ->body('Loyalty program settings updated')
            ->success()
            ->send();
    }
}
