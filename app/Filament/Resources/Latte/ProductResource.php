<?php

namespace App\Filament\Resources\Latte;

use App\Filament\Resources\Latte\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Menu';
    protected static ?string $navigationGroup = 'Latte';
    protected static ?int $navigationSort = 47;
    protected static ?string $modelLabel = 'Menu';
    protected static ?string $pluralModelLabel = 'Menu';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(60),
            Forms\Components\Select::make('category')->label('Kategori')->options(Product::CATEGORIES)->required()->default('kopi'),
            Forms\Components\TextInput::make('price')->label('Harga')->prefix('Rp')->numeric()->minValue(0)->maxValue(10_000_000)->required(),
            Forms\Components\TextInput::make('sort')->label('Urutan')->numeric()->default(0)->helperText('Angka kecil tampil lebih dulu di kasir.'),
            Forms\Components\Toggle::make('is_active')->label('Tampil di kasir')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => Product::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Kategori')->options(Product::CATEGORIES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProducts::route('/')];
    }
}
