<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.settings';

    private const LABELS = [
        'carwash' => 'Car Wash',
        'motorwash' => 'Motor Wash',
        'coffeeshop' => 'Coffee Shop',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            collect(SystemSetting::LOYALTY_TYPES)
                ->mapWithKeys(fn (string $type) => [
                    "{$type}_milestones" => SystemSetting::milestones($type),
                ])
                ->all()
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Current Configuration')
                    ->description('Active milestones per loyalty program')
                    ->schema([
                        Grid::make(3)->schema(
                            collect(SystemSetting::LOYALTY_TYPES)
                                ->map(fn (string $type) => Placeholder::make("current_{$type}")
                                    ->label(self::LABELS[$type])
                                    ->content(fn () => new HtmlString(
                                        collect(SystemSetting::milestones($type))
                                            ->map(fn (array $m) => e($m['at'] . ' poin — ' . $m['reward']))
                                            ->implode('<br>')
                                        . '<br><strong>Max: ' . SystemSetting::maxPoints($type) . ' poin</strong>'
                                    )))
                                ->all()
                        ),
                    ])
                    ->collapsible()
                    ->compact(),

                ...collect(SystemSetting::LOYALTY_TYPES)
                    ->map(fn (string $type) => Section::make(self::LABELS[$type] . ' Milestones')
                        ->description('Poin dikumpulkan terus sampai milestone terakhir (max point). Reset ke 0 hanya saat hadiah diklaim.')
                        ->schema([
                            Repeater::make("{$type}_milestones")
                                ->label('Milestone')
                                ->schema([
                                    TextInput::make('at')
                                        ->label('Tercapai di poin')
                                        ->helperText('Hadiah bisa ditukar mulai poin ini')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->maxValue(1000)
                                        ->distinct()
                                        ->required()
                                        ->suffix('poin'),

                                    TextInput::make('reward')
                                        ->label('Nama hadiah')
                                        ->maxLength(100)
                                        ->required(),
                                ])
                                ->columns(2)
                                ->minItems(1)
                                ->required()
                                ->live(onBlur: true)
                                ->reorderable(false)
                                ->defaultItems(1)
                                ->addActionLabel('Tambah milestone')
                                ->itemLabel(fn (array $state) => filled($state['at'] ?? null)
                                    ? "{$state['at']} poin — " . ($state['reward'] ?? '')
                                    : null),

                            Placeholder::make("{$type}_affected")
                                ->label('')
                                ->content(fn (Get $get) => $this->affectedCustomersWarning($type, $get("{$type}_milestones") ?? []))
                                ->visible(fn (Get $get) => $this->affectedCustomers($type, $get("{$type}_milestones") ?? [])->isNotEmpty()),
                        ]))
                    ->all(),
            ])
            ->statePath('data');
    }

    /**
     * Customers sitting above the max point the admin is about to save.
     * Their points are never trimmed — this is a heads-up, not an action.
     */
    private function affectedCustomers(string $type, array $milestones)
    {
        $newMax = collect(SystemSetting::normalizeMilestones($milestones))->max('at');

        if (! $newMax) {
            return collect();
        }

        return Customer::query()
            ->with('user:id,name,phone')
            ->where("{$type}_points", '>', $newMax)
            ->orderByDesc("{$type}_points")
            ->limit(100)
            ->get(['id', 'user_id', "{$type}_points"]);
    }

    private function affectedCustomersWarning(string $type, array $milestones): HtmlString
    {
        $newMax = (int) collect(SystemSetting::normalizeMilestones($milestones))->max('at');
        $customers = $this->affectedCustomers($type, $milestones);

        $rows = $customers
            ->map(fn (Customer $c) => '<tr>'
                . '<td class="py-1 pr-4">' . e($c->user?->name ?? '-') . '</td>'
                . '<td class="py-1 pr-4">' . e($c->user?->phone ?? '-') . '</td>'
                . '<td class="py-1 pr-4 font-semibold">' . $c->getPoints($type) . '</td>'
                . '<td class="py-1">' . e($c->earnedMilestone($type)['reward'] ?? '-') . '</td>'
                . '</tr>')
            ->implode('');

        return new HtmlString(
            '<div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-700 dark:bg-warning-950">'
            . '<p class="font-semibold text-warning-700 dark:text-warning-300">Perhatian: ' . $customers->count()
            . ' customer punya poin di atas max baru (' . $newMax . ' poin)</p>'
            . '<p class="mb-2 text-warning-700 dark:text-warning-300">Poin mereka tidak dipotong. Mereka dihitung di milestone tertinggi dan bisa langsung klaim.</p>'
            . '<div class="overflow-x-auto"><table class="w-full text-left">'
            . '<thead><tr><th class="pr-4">Nama</th><th class="pr-4">No. HP</th><th class="pr-4">Poin</th><th>Hadiah saat ini</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table></div>'
            . ($customers->count() >= 100 ? '<p class="mt-2">Menampilkan 100 teratas.</p>' : '')
            . '</div>'
        );
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

        foreach (SystemSetting::LOYALTY_TYPES as $type) {
            $milestones = SystemSetting::normalizeMilestones($data["{$type}_milestones"] ?? []);

            if ($milestones === []) {
                Notification::make()
                    ->title('Gagal menyimpan')
                    ->body(self::LABELS[$type] . ' harus punya minimal 1 milestone.')
                    ->danger()
                    ->send();

                return;
            }

            $top = end($milestones);

            SystemSetting::set("{$type}_milestones", json_encode($milestones), self::LABELS[$type] . ' milestone tiers');
            SystemSetting::set("{$type}_reward_threshold", $top['at'], 'Max points before reset (last milestone)');
            SystemSetting::set("{$type}_reward_message", $top['reward'], 'Top tier reward name');
        }

        SystemSetting::clearCache();

        Notification::make()
            ->title('Settings saved successfully')
            ->body('Milestone loyalty berhasil diperbarui')
            ->success()
            ->send();
    }
}
