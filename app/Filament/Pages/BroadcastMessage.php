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
use App\Services\WhatsAppService;

class BroadcastMessage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Broadcast';

    protected static ?string $title = 'WhatsApp Broadcast';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.broadcast-message';

    public ?array $data = [];

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
                            ->label('Filter Customers')
                            ->options([
                                'all' => 'All Customers',
                                'has_reward' => 'Customers with Rewards',
                                'active' => 'Active Customers (Last 30 Days)',
                            ])
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $recipients = $this->getRecipientsByFilter($state);
                                $set('recipients', $recipients->pluck('id')->toArray());
                            }),

                        \Filament\Forms\Components\CheckboxList::make('recipients')
                            ->label('Select Recipients')
                            ->options(function ($get) {
                                // Show options based on current filter or all if not set
                                return $this->getRecipientsByFilter($get('target_filter') ?? 'all')
                                    ->mapWithKeys(function ($customer) {
                                        return [$customer->id => "{$customer->user->name} ({$customer->user->phone})"];
                                    });
                            })
                            ->default(function () {
                                return $this->getRecipientsByFilter('all')->pluck('id')->toArray();
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->required(),

                        Toggle::make('test_mode')
                            ->label('Test Mode')
                            ->helperText('Send to only 5 customers for testing')
                            ->default(false)
                            ->reactive(),
                    ]),

                Section::make('Cost Estimate')
                    ->schema([
                        Placeholder::make('cost_summary')
                            ->label('')
                            ->content(function ($get) {
                                $count = count($get('recipients') ?? []);
                                if ($get('test_mode')) {
                                    $count = min($count, 5);
                                }
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

    public function send(WhatsAppService $whatsappService): void
    {
        $data = $this->form->getState();

        // Get selected customers
        $recipientIds = $data['recipients'] ?? [];
        
        if (empty($recipientIds)) {
             Notification::make()
                ->title('No recipients selected')
                ->warning()
                ->send();
            return;
        }

        $query = Customer::with('user')->whereIn('id', $recipientIds);

        if ($data['test_mode']) {
            $query->limit(5);
        }

        $recipients = $query->get();

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
            'status' => 'sending',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        // Prepare batch payload
        $messages = $recipients->map(function ($customer) use ($data) {
            return [
                'phone' => $customer->user->phone,
                'message' => $this->personalizeMessage($data['message'], $customer),
            ];
        })->toArray();

        // Send batch
        $result = $whatsappService->sendBatch($messages);

        if ($result['success']) {
            $broadcast->update([
                'status' => 'sent',
                'sent_count' => $recipients->count(),
            ]);

            Notification::make()
                ->title('Broadcast sent successfully')
                ->body("Sent to {$recipients->count()} customers")
                ->success()
                ->send();
        } else {
            $broadcast->update(['status' => 'failed']);
            
            Notification::make()
                ->title('Broadcast failed')
                ->body($result['error'] ?? 'Unknown error')
                ->danger()
                ->send();
        }

        $this->form->fill();
    }

    private function personalizeMessage(string $message, Customer $customer): string
    {
        return str_replace(
            ['{name}', '{points}', '{visits}'],
            [
                $customer->user->name,
                $customer->current_points,
                $customer->total_visits
            ],
            $message
        );
    }

    private function getRecipientsByFilter(?string $filter)
    {
        $query = Customer::with('user');

        return match ($filter) {
            'has_reward' => $query->where('current_points', '>=', SystemSetting::rewardPointsThreshold())->get(),
            'active' => $query->where('last_visit_at', '>=', now()->subDays(30))->get(),
            default => $query->get(),
        };
    }
}
