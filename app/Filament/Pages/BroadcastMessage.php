<?php

namespace App\Filament\Pages;

use App\Jobs\SendBroadcastJob;
use App\Models\Broadcast;
use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class BroadcastMessage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Broadcast';

    protected static ?string $title = 'WhatsApp Broadcast';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.broadcast-message';

    public ?string $message = null;
    public ?string $target_filter = 'all';
    public bool $test_mode = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Message Content')
                    ->schema([
                        Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(6)
                            ->placeholder('Type your message here...')
                            ->helperText('Variables: {name}, {points}, {visits}'),
                    ]),

                Section::make('Target Audience')
                    ->schema([
                        Select::make('target_filter')
                            ->label('Send To')
                            ->required()
                            ->options([
                                'all' => 'All Customers',
                                'has_reward' => 'Customers with Rewards',
                                'active' => 'Active Customers (Last 30 Days)',
                            ])
                            ->default('all')
                            ->reactive(),

                        Toggle::make('test_mode')
                            ->label('Test Mode')
                            ->helperText('Send to only 5 customers for testing')
                            ->reactive(),
                    ]),

                Section::make('Cost Estimate')
                    ->schema([
                        Placeholder::make('cost_summary')
                            ->label('')
                            ->content(function ($get) {
                                $count = $this->getRecipientCount($get('target_filter'), $get('test_mode'));
                                $cost = $count * Broadcast::COST_PER_MESSAGE;
                                
                                return view('filament.components.cost-summary', [
                                    'count' => $count,
                                    'cost' => $cost,
                                ]);
                            }),
                    ])
                    ->compact(),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $recipients = $this->getRecipients($data['target_filter'], $data['test_mode']);

        if ($recipients->isEmpty()) {
            Notification::make()
                ->title('No recipients found')
                ->warning()
                ->send();
            return;
        }

        $broadcast = Broadcast::create([
            'message' => $data['message'],
            'target_filter' => $data['target_filter'],
            'total_recipients' => $recipients->count(),
            'estimated_cost' => $recipients->count() * Broadcast::COST_PER_MESSAGE,
            'status' => 'queued',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        foreach ($recipients as $customer) {
            SendBroadcastJob::dispatch($broadcast, $customer, $data['message'])
                ->delay(now()->addMilliseconds(100));
        }

        $broadcast->update(['status' => 'sending']);

        Notification::make()
            ->title('Broadcast queued successfully')
            ->body("Sending to {$recipients->count()} customers")
            ->success()
            ->send();

        $this->form->fill();
    }

    private function getRecipientCount(string $filter, bool $testMode): int
    {
        return $this->getRecipients($filter, $testMode)->count();
    }

    private function getRecipients(string $filter, bool $testMode)
    {
        $query = Customer::with('user');

        $query = match ($filter) {
            'has_reward' => $query->where('current_points', '>=', SystemSetting::rewardPointsThreshold()),
            'active' => $query->where('last_visit_at', '>=', now()->subDays(30)),
            default => $query,
        };

        if ($testMode) {
            $query->limit(5);
        }

        return $query->get();
    }
}
