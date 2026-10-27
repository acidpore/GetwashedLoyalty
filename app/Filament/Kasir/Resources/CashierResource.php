<?php

namespace App\Filament\Kasir\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Till logins. They can only reach this panel (see User::canAccessPanel). */
class CashierResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $slug = 'akun-kasir';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Akun Kasir';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $modelLabel = 'Akun Kasir';
    protected static ?string $pluralModelLabel = 'Akun Kasir';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'cashier');
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(60),
            Forms\Components\TextInput::make('email')->label('Email login')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')->label('Password')->password()->revealable()
                ->minLength(8)->required(fn (string $operation) => $operation === 'create')
                // The model's 'hashed' cast hashes it.
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Kosongkan kalau tidak diganti.' : 'Minimal 8 karakter.'),
            Forms\Components\Hidden::make('role')->default('cashier'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->date('d/m/Y')->visibleFrom('md'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => CashierResource\ManageCashiers::route('/')];
    }
}
