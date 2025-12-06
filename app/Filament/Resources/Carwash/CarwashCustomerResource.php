<?php

namespace App\Filament\Resources\Carwash;

use App\Filament\Resources\Carwash\CarwashCustomerResource\Pages;
use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarwashCustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customers';
    protected static ?string $navigationGroup = 'Car Wash';
    protected static ?int $navigationSort = 30;
    protected static ?string $modelLabel = 'Car Wash Customer';
    protected static ?string $pluralModelLabel = 'Car Wash Customers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Customer Information')
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->label('Name')
                        ->required(),
                    Forms\Components\TextInput::make('user.phone')
                        ->label('Phone')
                        ->tel()
                        ->required(),
                ]),
            
            Forms\Components\Section::make('Car Wash Loyalty')
                ->schema([
                    Forms\Components\TextInput::make('carwash_points')
                        ->label('Points')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('carwash_total_visits')
                        ->label('Total Visits')
                        ->numeric()
                        ->default(0),
                    Forms\Components\DateTimePicker::make('carwash_last_visit_at')
                        ->label('Last Visit'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('carwash_points')
                    ->label('Points')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state >= SystemSetting::carwashRewardThreshold() ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('carwash_total_visits')
                    ->label('Total Visits')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('carwash_last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('ready_for_reward')
                    ->label('Ready for Reward')
                    ->query(fn ($query) => $query->where('carwash_points', '>=', SystemSetting::carwashRewardThreshold())),
                
                Tables\Filters\Filter::make('active_customers')
                    ->label('Active (Last 30 Days)')
                    ->query(fn ($query) => $query->where('carwash_last_visit_at', '>=', now()->subDays(30))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('add_point')
                    ->label('Add Point')
                    ->icon('heroicon-o-plus-circle')
                    ->action(function (Customer $record) {
                        $record->addPoints('carwash');
                    })
                    ->requiresConfirmation(),
            ])
            ->defaultSort('carwash_last_visit_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('carwash_total_visits', '>', 0));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarwashCustomers::route('/'),
            'edit' => Pages\EditCarwashCustomer::route('/{record}/edit'),
        ];
    }
}
