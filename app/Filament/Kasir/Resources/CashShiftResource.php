<?php

namespace App\Filament\Kasir\Resources;

use App\Models\CashShift;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Read-only history; shifts are opened and closed from the till. */
class CashShiftResource extends Resource
{
    protected static ?string $model = CashShift::class;
    protected static ?string $slug = 'shift';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Shift Kasir';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Shift';
    protected static ?string $pluralModelLabel = 'Shift Kasir';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $rp = fn ($state) => $state === null ? '-' : 'Rp' . number_format($state, 0, ',', '.');

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user')
                ->withCount(['orders as sales_count' => fn ($q) => $q->paid()])
                ->withSum(['orders as sales_total' => fn ($q) => $q->paid()], 'total'))
            ->defaultSort('opened_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Kasir')->weight('semibold')
                    ->description(fn (CashShift $record) => $record->opened_at->format('d/m H:i') . ' – ' . ($record->closed_at?->format('H:i') ?? 'sekarang')),
                Tables\Columns\TextColumn::make('closed_at')->label('Status')->badge()
                    ->state(fn (CashShift $record) => $record->closed_at ? 'Ditutup' : 'Berjalan')
                    ->color(fn (string $state) => $state === 'Berjalan' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('sales_total')->label('Penjualan')->formatStateUsing($rp)
                    ->description(fn (CashShift $record) => $record->sales_count . ' transaksi')->visibleFrom('md'),
                Tables\Columns\TextColumn::make('opening_cash')->label('Modal')->formatStateUsing($rp)->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('expected_cash')->label('Seharusnya')->formatStateUsing($rp)->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('closing_cash')->label('Dihitung')->formatStateUsing($rp)->visibleFrom('md'),
                Tables\Columns\TextColumn::make('difference')->label('Selisih')->badge()
                    ->state(fn (CashShift $record) => $record->difference())
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : ($state > 0 ? '+' : ($state < 0 ? '-' : '')) . 'Rp' . number_format(abs($state), 0, ',', '.'))
                    ->color(fn ($state) => $state === null ? 'gray' : ($state === 0 ? 'success' : 'danger')),
                Tables\Columns\TextColumn::make('note')->label('Catatan')->limit(40)->placeholder('-')->visibleFrom('xl'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => CashShiftResource\ListShifts::route('/')];
    }
}
