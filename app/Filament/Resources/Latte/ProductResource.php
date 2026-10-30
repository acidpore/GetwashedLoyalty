<?php

namespace App\Filament\Resources\Latte;

use App\Filament\Resources\Latte\ProductResource\Pages;
use App\Models\Ingredient;
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

    public static function getNavigationGroup(): ?string
    {
        return filament()->getCurrentPanel()?->getId() === 'kasir' ? 'Produk' : 'Latte';
    }

    // Prices are the owner's call; cashiers only sell.
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        $rp = fn (string $name, string $label) => Forms\Components\TextInput::make($name)->label($label)->prefix('Rp')->numeric()->minValue(0)->maxValue(10_000_000);

        return $form->schema([
            Forms\Components\Tabs::make()->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make('Info')->icon('heroicon-m-information-circle')->columns(2)->schema([
                    Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(60),
                    Forms\Components\Select::make('category')->label('Kategori')->options(Product::CATEGORIES)->required()->default('kopi'),
                    $rp('price', 'Harga')->required(),
                    Forms\Components\TextInput::make('sort')->label('Urutan')->numeric()->default(0)->helperText('Angka kecil tampil lebih dulu di kasir.'),
                    Forms\Components\FileUpload::make('image')->label('Foto')->image()->imageEditor()->imageCropAspectRatio('1:1')->imageResizeTargetWidth(600)
                        ->disk('public')->directory('menu')->maxSize(4096)->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')->label('Tampil di kasir')->default(true),
                ]),
                Forms\Components\Tabs\Tab::make('Varian')->icon('heroicon-m-adjustments-horizontal')->schema([
                    Forms\Components\Repeater::make('options')->hiddenLabel()->addActionLabel('Tambah grup varian')
                        ->helperText('Contoh: grup "Suhu" (Hot / Ice), "Ukuran" (Regular / Large), "Tambahan" (Extra shot, Oat milk) dengan pilih banyak.')
                        ->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? null)->defaultItems(0)
                        ->schema([
                            Forms\Components\TextInput::make('name')->label('Nama grup')->required()->maxLength(30),
                            Forms\Components\Toggle::make('multiple')->label('Boleh pilih lebih dari satu')->inline(false),
                            Forms\Components\Repeater::make('choices')->label('Pilihan')->addActionLabel('Tambah pilihan')->columnSpanFull()
                                ->minItems(1)->columns(2)->grid(['default' => 1, 'md' => 2])
                                ->schema([
                                    Forms\Components\TextInput::make('name')->label('Pilihan')->required()->maxLength(30),
                                    $rp('price', 'Tambahan harga')->default(0),
                                ]),
                        ])->columns(2),
                ]),
                Forms\Components\Tabs\Tab::make('Resep')->icon('heroicon-m-beaker')->schema([
                    Forms\Components\Repeater::make('recipe')->relationship()->hiddenLabel()->addActionLabel('Tambah bahan')
                        ->helperText('Bahan yang terpakai untuk 1 porsi. Stok bahan berkurang otomatis setiap terjual.')
                        ->defaultItems(0)->columns(2)
                        ->schema([
                            Forms\Components\Select::make('ingredient_id')->label('Bahan')->required()->searchable()->preload()
                                ->options(fn () => Ingredient::orderBy('name')->get()->mapWithKeys(fn ($i) => [$i->id => "{$i->name} ({$i->unit})"]))
                                ->distinct(),
                            Forms\Components\TextInput::make('qty')->label('Jumlah per porsi')->numeric()->minValue(0.01)->required(),
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->square()->size(44)
                    ->defaultImageUrl(fn () => null),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->weight('semibold')
                    ->description(fn (Product $record) => collect($record->options)->pluck('name')->implode(' · ') ?: null),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => Product::CATEGORIES[$state] ?? $state)->visibleFrom('md'),
                Tables\Columns\TextColumn::make('price')->label('Harga')->formatStateUsing(fn ($state) => 'Rp' . number_format((int) $state, 0, ',', '.'))->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Kategori')->options(Product::CATEGORIES),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->modalWidth('3xl'),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageProducts::route('/')];
    }
}
