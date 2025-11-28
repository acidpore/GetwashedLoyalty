<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('User Account')
                            ->helperText('Link to user account'),
                        
                        Forms\Components\TextInput::make('current_points')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Current Points')
                            ->helperText('Active points for rewards'),
                        
                        Forms\Components\TextInput::make('total_visits')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label('Total Visits')
                            ->helperText('Lifetime visit count'),
                        
                        Forms\Components\DateTimePicker::make('last_visit_at')
                            ->label('Last Visit')
                            ->displayFormat('d/m/Y H:i')
                            ->nullable(),
                    ])->columns(2),
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
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->icon('heroicon-m-phone'),
                
                Tables\Columns\TextColumn::make('current_points')
                    ->label('Points')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 5 => 'success',
                        $state >= 3 => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('total_visits')
                    ->label('Total Visits')
                    ->sortable()
                    ->icon('heroicon-m-chart-bar'),
                
                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->last_visit_at?->format('d/m/Y H:i')),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_reward')
                    ->label('Ready for Reward')
                    ->query(fn (Builder $query): Builder => $query->where('current_points', '>=', 5)),
                
                Tables\Filters\Filter::make('active_customers')
                    ->label('Active (Last 30 Days)')
                    ->query(fn (Builder $query): Builder => $query->where('last_visit_at', '>=', now()->subDays(30))),
                
                Tables\Filters\SelectFilter::make('points_range')
                    ->label('Points Range')
                    ->options([
                        '0-2' => '0-2 points',
                        '3-4' => '3-4 points',
                        '5+' => '5+ points (Reward)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            '0-2' => $query->whereBetween('current_points', [0, 2]),
                            '3-4' => $query->whereBetween('current_points', [3, 4]),
                            '5+' => $query->where('current_points', '>=', 5),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reset_points')
                    ->label('Reset Points')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Customer $record) => $record->resetPoints())
                    ->visible(fn (Customer $record) => $record->current_points > 0),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('admin.export.customers'))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_visit_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
