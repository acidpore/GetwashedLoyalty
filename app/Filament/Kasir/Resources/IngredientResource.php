<?php

namespace App\Filament\Kasir\Resources;

use App\Models\Ingredient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;
    protected static ?string $slug = 'stok';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Stok Bahan';
    protected static ?string $navigationGroup = 'Produk';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Bahan';
    protected static ?string $pluralModelLabel = 'Stok Bahan';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return Ingredient::low()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('Nama bahan')->required()->maxLength(60),
            Forms\Components\TextInput::make('unit')->label('Satuan')->required()->maxLength(16)->placeholder('gr, ml, pcs')->datalist(['gr', 'ml', 'pcs', 'kg', 'liter']),
            Forms\Components\TextInput::make('stock')->label('Stok awal')->numeric()->default(0)->hiddenOn('edit'),
            Forms\Components\TextInput::make('min_stock')->label('Batas stok rendah')->numeric()->default(0)->helperText('Muncul peringatan kalau stok di bawah angka ini.'),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\RepeatableEntry::make('movements')->label('Riwayat penyesuaian (20 terakhir)')->columnSpanFull()->columns(3)
                ->state(fn (Ingredient $record) => $record->movements()->with('user')->latest('id')->limit(20)->get())
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')->hiddenLabel()->dateTime('d/m/Y H:i'),
                    Infolists\Components\TextEntry::make('qty')->hiddenLabel()->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . rtrim(rtrim(number_format($state, 2, ',', '.'), '0'), ','))
                        ->color(fn ($state) => $state > 0 ? 'success' : 'danger')->weight('semibold'),
                    Infolists\Components\TextEntry::make('reason')->hiddenLabel()->helperText(fn ($record) => $record->user?->name),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $fmt = fn ($state) => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',');

        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Bahan')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('stock')->label('Stok')->sortable()
                    ->formatStateUsing(fn ($state, Ingredient $record) => $fmt($state) . ' ' . $record->unit)
                    ->badge()->color(fn (Ingredient $record) => $record->stock <= $record->min_stock ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('min_stock')->label('Batas rendah')->visibleFrom('md')
                    ->formatStateUsing(fn ($state, Ingredient $record) => $fmt($state) . ' ' . $record->unit),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->since()->visibleFrom('lg'),
            ])
            ->filters([
                Tables\Filters\Filter::make('low')->label('Stok rendah')->query(fn ($query) => $query->low()),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust')->label('Stok')->icon('heroicon-m-plus-circle')->button()->size('sm')
                    ->modalHeading(fn (Ingredient $record) => 'Sesuaikan stok ' . $record->name)
                    ->form([
                        Forms\Components\ToggleButtons::make('type')->label('Jenis')->inline()->required()->default('in')
                            ->options(['in' => 'Barang masuk', 'out' => 'Terbuang / rusak', 'set' => 'Hasil stock opname']),
                        Forms\Components\TextInput::make('qty')->label('Jumlah')->numeric()->minValue(0)->required()
                            ->suffix(fn (Ingredient $record) => $record->unit),
                        Forms\Components\TextInput::make('reason')->label('Catatan')->maxLength(120)->placeholder('mis. belanja supplier'),
                    ])
                    ->action(function (Ingredient $record, array $data) {
                        $qty = (float) $data['qty'];
                        $delta = match ($data['type']) {
                            'in' => $qty,
                            'out' => -$qty,
                            'set' => $qty - $record->stock,
                        };
                        $label = ['in' => 'Barang masuk', 'out' => 'Terbuang', 'set' => 'Stock opname'][$data['type']];
                        $record->adjust($delta, trim($label . ($data['reason'] ? ': ' . $data['reason'] : '')), auth()->id());
                        Notification::make()->title('Stok diperbarui')->success()->send();
                    }),
                Tables\Actions\ViewAction::make()->label('Riwayat')->modalHeading(fn (Ingredient $record) => $record->name),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => IngredientResource\ManageIngredients::route('/')];
    }
}
