<?php

namespace App\Filament\Resources\Latte;

use App\Filament\Resources\Latte\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            Infolists\Components\TextEntry::make('cashier.name')->label('Kasir')->placeholder('-'),
            Infolists\Components\TextEntry::make('customer.user.name')->label('Customer')->placeholder('Umum'),
            Infolists\Components\TextEntry::make('payment_method')->label('Bayar')->formatStateUsing(fn ($state) => Order::METHODS[$state] ?? $state),
            Infolists\Components\RepeatableEntry::make('items')->label('Item')->columnSpanFull()->columns(3)->schema([
                Infolists\Components\TextEntry::make('name')->hiddenLabel()->weight('semibold')
                    ->description(fn ($record) => $record->note),
                Infolists\Components\TextEntry::make('qty')->hiddenLabel()->formatStateUsing(fn ($record) => $record->qty . ' × Rp' . number_format($record->price, 0, ',', '.')),
                Infolists\Components\TextEntry::make('price')->hiddenLabel()->alignEnd()
                    ->formatStateUsing(fn ($record) => $record->price ? 'Rp' . number_format($record->price * $record->qty, 0, ',', '.') : 'Gratis'),
            ]),
            Infolists\Components\TextEntry::make('total')->label('Total')->money('IDR', locale: 'id')->weight('bold'),
            Infolists\Components\TextEntry::make('paid')->label('Dibayar')->money('IDR', locale: 'id'),
            Infolists\Components\TextEntry::make('change')->label('Kembalian')->money('IDR', locale: 'id'),
            Infolists\Components\TextEntry::make('points_earned')->label('Poin')->formatStateUsing(fn ($state) => '+' . $state),
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
                    ->formatStateUsing(fn ($state) => Order::METHODS[$state] ?? $state)
                    ->color(fn ($state) => $state === 'cash' ? 'gray' : 'info')->visibleFrom('sm'),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('IDR', locale: 'id')->sortable()->weight('semibold')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Omzet')->money('IDR', locale: 'id')),
                Tables\Columns\TextColumn::make('cashier.name')->label('Kasir')->placeholder('-')->visibleFrom('lg'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Metode bayar')->options(Order::METHODS),
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
            ->actions([Tables\Actions\ViewAction::make()->modalWidth('2xl')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListOrders::route('/')];
    }
}
