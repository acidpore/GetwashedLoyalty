<?php

namespace App\Filament\Resources\Latte;

use App\Filament\Resources\Latte\OrderResource\Pages;
use App\Models\Order;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Transaksi';
    protected static ?string $navigationGroup = 'Latte';
    protected static ?int $navigationSort = 46;
    protected static ?string $modelLabel = 'Transaksi';
    protected static ?string $pluralModelLabel = 'Transaksi';

    // Same resource lives in the admin panel (group "Latte") and the till panel (group "Transaksi").
    public static function getNavigationGroup(): ?string
    {
        return filament()->getCurrentPanel()?->getId() === 'kasir' ? 'Transaksi' : 'Latte';
    }

    public static function getNavigationLabel(): string
    {
        return filament()->getCurrentPanel()?->getId() === 'kasir' ? 'Riwayat Penjualan' : 'Transaksi';
    }

    public static function getNavigationSort(): ?int
    {
        return filament()->getCurrentPanel()?->getId() === 'kasir' ? 2 : 46;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Owners can void anything; a cashier only their own sales from today. */
    public static function canVoid(Order $order): bool
    {
        $user = auth()->user();

        return ! $order->voided_at && ($user?->isAdmin() || ($order->cashier_id === $user?->id && $order->created_at->isToday()));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $rp = fn ($state) => 'Rp' . number_format((int) $state, 0, ',', '.');

        return $infolist->schema([
            Infolists\Components\TextEntry::make('void_reason')->hiddenLabel()->columnSpanFull()->visible(fn (Order $record) => $record->voided_at)
                ->formatStateUsing(fn (Order $record) => 'Dibatalkan ' . $record->voided_at->format('d/m/Y H:i') . ' oleh ' . ($record->voidedBy?->name ?? '-') . ': ' . $record->void_reason)
                ->badge()->color('danger'),
            Infolists\Components\TextEntry::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            Infolists\Components\TextEntry::make('cashier.name')->label('Kasir')->placeholder('-'),
            Infolists\Components\TextEntry::make('customer.user.name')->label('Customer')->placeholder('Umum'),
            Infolists\Components\TextEntry::make('payment_method')->label('Bayar')->formatStateUsing(fn ($state) => Order::METHODS[$state] ?? $state),
            Infolists\Components\RepeatableEntry::make('items')->label('Item')->columnSpanFull()->columns(3)->schema([
                Infolists\Components\TextEntry::make('name')->hiddenLabel()->weight('semibold')
                    ->helperText(fn ($record) => collect([$record->options, $record->note])->filter()->implode(' · ') ?: null),
                Infolists\Components\TextEntry::make('qty')->hiddenLabel()->formatStateUsing(fn ($record) => $record->qty . ' × ' . $rp($record->price)),
                Infolists\Components\TextEntry::make('price')->hiddenLabel()->alignEnd()
                    ->formatStateUsing(fn ($record) => $record->price ? $rp($record->price * $record->qty) : 'Gratis'),
            ]),
            Infolists\Components\TextEntry::make('subtotal')->label('Subtotal')->formatStateUsing($rp),
            Infolists\Components\TextEntry::make('discount')->label('Diskon')->formatStateUsing(fn ($state) => $state ? '-' . $rp($state) : '-')
                ->helperText(fn (Order $record) => $record->promo_name),
            Infolists\Components\TextEntry::make('total')->label('Total')->formatStateUsing($rp)->weight('bold'),
            Infolists\Components\TextEntry::make('change')->label('Kembalian')->formatStateUsing($rp)
                ->helperText(fn (Order $record) => 'Dibayar ' . $rp($record->paid)),
        ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer.user', 'cashier'])->withSum('items', 'qty'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Waktu')->dateTime('d/m H:i')->sortable()
                    ->description(fn (Order $record) => $record->number),
                Tables\Columns\TextColumn::make('customer.user.name')->label('Customer')->placeholder('Umum')
                    ->searchable(['name', 'phone'])->visibleFrom('md'),
                Tables\Columns\TextColumn::make('items_sum_qty')->label('Item')->suffix(' item')->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('payment_method')->label('Bayar')->badge()
                    ->formatStateUsing(fn ($state, Order $record) => $record->voided_at ? 'Dibatalkan' : (Order::METHODS[$state] ?? $state))
                    ->color(fn ($state, Order $record) => $record->voided_at ? 'danger' : ($state === 'cash' ? 'success' : 'info'))->visibleFrom('sm'),
                Tables\Columns\TextColumn::make('total')->label('Total')->formatStateUsing(fn ($state) => 'Rp' . number_format((int) $state, 0, ',', '.'))->sortable()->weight('semibold')
                    ->extraAttributes(fn (Order $record) => $record->voided_at ? ['style' => 'text-decoration:line-through;opacity:.5'] : [])
                    ->description(fn (Order $record) => $record->discount ? 'Diskon Rp' . number_format($record->discount, 0, ',', '.') : null)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Omzet (tanpa batal)')->formatStateUsing(fn ($state) => 'Rp' . number_format((int) $state, 0, ',', '.'))
                        ->query(fn ($query) => $query->whereNull('voided_at'))),
                Tables\Columns\TextColumn::make('cashier.name')->label('Kasir')->placeholder('-')->visibleFrom('lg'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Metode bayar')->options(Order::METHODS),
                Tables\Filters\TernaryFilter::make('voided_at')->label('Status')->nullable()
                    ->placeholder('Semua')->trueLabel('Dibatalkan')->falseLabel('Berhasil'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari')->default(today()),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ($data['from'] ?? null) ? 'Dari ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y') : null,
                        ($data['until'] ?? null) ? 'Sampai ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y') : null,
                    ])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->modalWidth('2xl'),
                Tables\Actions\Action::make('whatsapp')->label('Kirim struk WA')->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->visible(fn (Order $record) => ! $record->voided_at && $record->customer?->user?->phone)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Order $record) => 'Struk dikirim ke ' . $record->customer->user->phone . '.')
                    ->action(function (Order $record) {
                        $sent = app(WhatsAppService::class)->sendMessage($record->customer->user->phone, $record->load('items')->receiptText());
                        Notification::make()->title($sent ? 'Struk terkirim' : 'Gagal kirim WhatsApp')->{$sent ? 'success' : 'danger'}()->send();
                    }),
                Tables\Actions\Action::make('void')->label('Batalkan')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Order $record) => static::canVoid($record))
                    ->modalHeading(fn (Order $record) => 'Batalkan ' . $record->number . '?')
                    ->modalDescription('Stok bahan dikembalikan dan poin customer dari transaksi ini ditarik. Tidak bisa diurungkan.')
                    ->form([
                        Forms\Components\TextInput::make('reason')->label('Alasan')->required()->maxLength(120)->placeholder('mis. salah input menu'),
                    ])
                    ->action(function (Order $record, array $data) {
                        abort_unless(static::canVoid($record), 403);
                        $record->void($data['reason'], auth()->id());
                        Notification::make()->title('Transaksi dibatalkan')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrders::route('/')];
    }
}
