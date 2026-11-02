<?php

namespace App\Filament\Kasir\Resources;

use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Pengeluaran';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Pengeluaran';
    protected static ?string $pluralModelLabel = 'Pengeluaran';
    protected static ?string $slug = 'pengeluaran';

    public static function canAccess(): bool
    {
        return auth()->user()?->managesLatte() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('Untuk apa')->required()->maxLength(80)->placeholder('mis. Susu UHT 12 liter')->columnSpanFull(),
            Forms\Components\TextInput::make('amount')->label('Jumlah')->prefix('Rp')->numeric()->minValue(1)->required(),
            Forms\Components\DatePicker::make('paid_on')->label('Tanggal bayar')->default(now())->required(),
            Forms\Components\ToggleButtons::make('method')->label('Dibayar pakai')->options(Expense::METHODS)->inline()->required()->default('cash'),
            Forms\Components\TextInput::make('spread_weeks')->label('Dibagi ke berapa minggu')->numeric()->minValue(1)->maxValue(12)->default(1)->required()
                ->helperText('1 = masuk minggu ini saja. 3 = dibebankan rata ke 3 minggu mulai tanggal bayar.'),
            Forms\Components\TextInput::make('note')->label('Catatan')->maxLength(160)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $rp = fn ($state) => 'Rp' . number_format((int) $state, 0, ',', '.');

        return $table
            ->defaultSort('paid_on', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('paid_on')->label('Tanggal')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Untuk')->searchable()->weight('semibold')->description(fn (Expense $record) => $record->note),
                Tables\Columns\TextColumn::make('method')->label('Bayar')->badge()
                    ->formatStateUsing(fn ($state) => Expense::METHODS[$state] ?? $state)
                    ->color(fn ($state) => $state === 'cash' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('amount')->label('Jumlah')->formatStateUsing($rp)->sortable()
                    ->description(fn (Expense $record) => $record->spread_weeks > 1 ? "Dibagi {$record->spread_weeks} minggu" : null)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->formatStateUsing($rp)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')->label('Dibayar pakai')->options(Expense::METHODS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ExpenseResource\ManageExpenses::route('/')];
    }
}
