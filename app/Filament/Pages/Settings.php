<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
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
    protected static ?string $navigationLabel = 'Pengaturan Hadiah';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.settings';

    private const LABELS = [
        'carwash' => 'Car Wash',
        'motorwash' => 'Motor Wash',
        'coffeeshop' => 'Coffee Shop',
    ];

    private const ICONS = [
        'carwash' => 'heroicon-o-truck',
        'motorwash' => 'heroicon-o-bolt',
        'coffeeshop' => 'heroicon-o-beaker',
    ];

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Pengaturan Hadiah';
    }

    public function getSubheading(): ?string
    {
        return 'Atur milestone poin dan hadiah untuk tiap program loyalty.';
    }

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
                Tabs::make('programs')
                    ->persistTabInQueryString()
                    ->tabs(
                        collect(SystemSetting::LOYALTY_TYPES)
                            ->map(fn (string $type) => Tab::make(self::LABELS[$type])
                                ->icon(self::ICONS[$type])
                                ->badge(fn (Get $get) => count($get("{$type}_milestones") ?? []))
                                ->schema([
                                    Grid::make(['default' => 1, 'lg' => 3])->schema([
                                        Repeater::make("{$type}_milestones")
                                            ->label('Milestone')
                                            ->schema([
                                                TextInput::make('at')
                                                    ->label('Poin')
                                                    ->numeric()
                                                    ->integer()
                                                    ->minValue(1)
                                                    ->maxValue(1000)
                                                    ->distinct()
                                                    ->required()
                                                    ->suffix('poin')
                                                    ->columnSpan(['default' => 4, 'sm' => 1]),

                                                TextInput::make('reward')
                                                    ->label('Hadiah')
                                                    ->placeholder('Contoh: Gratis cuci 1 mobil')
                                                    ->maxLength(100)
                                                    ->required()
                                                    ->columnSpan(['default' => 4, 'sm' => 3]),
                                            ])
                                            ->columns(4)
                                            ->minItems(1)
                                            ->required()
                                            ->live(onBlur: true)
                                            ->reorderable(false)
                                            ->defaultItems(1)
                                            ->addActionLabel('Tambah milestone')
                                            ->columnSpan(['lg' => 2]),

                                        Placeholder::make("{$type}_flow")
                                            ->label('Alur customer')
                                            ->content(fn (Get $get) => $this->flowSummary($get("{$type}_milestones") ?? [])),
                                    ]),

                                    Placeholder::make("{$type}_impact")
                                        ->hiddenLabel()
                                        ->content(fn (Get $get) => $this->impactWarning($type, $get("{$type}_milestones") ?? []))
                                        ->visible(fn (Get $get) => $this->hasImpact($type, $get("{$type}_milestones") ?? [])),
                                ]))
                            ->all()
                    ),
            ])
            ->statePath('data');
    }

    /** Spells out the rule so admins see exactly what customers will go through. */
    private function flowSummary(array $milestones): HtmlString
    {
        $steps = collect(SystemSetting::normalizeMilestones($milestones));

        return new HtmlString(view('filament.components.milestone-flow', ['steps' => $steps])->render());
    }

    /**
     * Customers the new config would surprise:
     * - holding a reward today that the new first milestone moves out of reach
     * - above the new max (points are kept, they can claim the top tier right away)
     */
    private function impactedCustomers(string $type, array $milestones): array
    {
        $new = collect(SystemSetting::normalizeMilestones($milestones));

        if ($new->isEmpty()) {
            return [collect(), collect()];
        }

        $columns = ['id', 'user_id', "{$type}_points"];
        $query = fn () => Customer::query()->with('user:id,name,phone')->orderByDesc("{$type}_points")->limit(100);

        $oldFirst = SystemSetting::firstMilestonePoints($type);
        $newFirst = $new->first()['at'];

        $losingClaim = $newFirst > $oldFirst
            ? $query()
                ->where("{$type}_points", '>=', $oldFirst)
                ->where("{$type}_points", '<', $newFirst)
                ->where("{$type}_claimed_points", 0)
                ->get($columns)
            : collect();

        $aboveMax = $query()
            ->where("{$type}_points", '>', $new->max('at'))
            ->get($columns);

        return [$losingClaim, $aboveMax];
    }

    private function hasImpact(string $type, array $milestones): bool
    {
        return collect($this->impactedCustomers($type, $milestones))->contains(fn ($group) => $group->isNotEmpty());
    }

    private function impactWarning(string $type, array $milestones): HtmlString
    {
        [$losingClaim, $aboveMax] = $this->impactedCustomers($type, $milestones);

        $groups = array_filter([
            [
                'title' => $losingClaim->count() . ' customer kehilangan hak klaim',
                'body' => 'Poin mereka cukup untuk hadiah pertama yang sekarang, tapi belum sampai milestone pertama yang baru. Klaimkan hadiah mereka dulu sebelum menyimpan.',
                'customers' => $losingClaim,
            ],
            [
                'title' => $aboveMax->count() . ' customer di atas poin maksimal baru',
                'body' => 'Poin mereka tidak dipotong. Mereka bisa langsung klaim hadiah tertinggi.',
                'customers' => $aboveMax,
            ],
        ], fn (array $group) => $group['customers']->isNotEmpty());

        return new HtmlString(view('filament.components.milestone-impact', [
            'type' => $type,
            'groups' => $groups,
        ])->render());
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
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
            ->title('Pengaturan tersimpan')
            ->body('Milestone dan hadiah berhasil diperbarui.')
            ->success()
            ->send();
    }
}
