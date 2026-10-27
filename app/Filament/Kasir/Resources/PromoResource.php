<?php

namespace App\Filament\Kasir\Resources;

use App\Models\Promo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoResource extends Resource
{
    protected static ?string $model = Promo::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Promo';
    protected static ?string $navigationGroup = 'Produk';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Promo';
    protected static ?string $pluralModelLabel = 'Promo';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('Nama promo')->required()->maxLength(40)->placeholder('mis. Diskon Mahasiswa')->columnSpanFull(),
            Forms\Components\ToggleButtons::make('type')->label('Jenis')->options(Promo::TYPES)->inline()->required()->default('percent')->live(),
            Forms\Components\TextInput::make('value')->label('Besar diskon')->numeric()->required()->minValue(1)
                ->maxValue(fn (Forms\Get $get) => $get('type') === 'percent' ? 100 : 10_000_000)
                ->prefix(fn (Forms\Get $get) => $get('type') === 'amount' ? 'Rp' : null)
                ->suffix(fn (Forms\Get $get) => $get('type') === 'percent' ? '%' : null),
            Forms\Components\Toggle::make('is_active')->label('Bisa dipilih di kasir')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Promo')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('value')->label('Diskon')->formatStateUsing(fn (Promo $record) => $record->label())->badge()->color('primary'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => PromoResource\ManagePromos::route('/')];
    }
}
