<?php

namespace App\Filament\Resources\Coffeeshop;

use App\Filament\Resources\Coffeeshop\CoffeeshopVisitHistoryResource\Pages;
use App\Models\VisitHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CoffeeshopVisitHistoryResource extends Resource
{
    protected static ?string $model = VisitHistory::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Visit History';
    protected static ?string $navigationGroup = 'Coffee Shop';
    protected static ?int $navigationSort = 51;
    protected static ?string $modelLabel = 'Visit';
    protected static ?string $pluralModelLabel = 'Visit Histories';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with('customer.user')
                ->whereJsonContains('loyalty_types', 'coffeeshop')
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('customer.user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('points_earned')
                    ->label('Points Earned')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('visited_at')
                    ->label('Check-In Time')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->visited_at->format('d/m/Y H:i:s')),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-m-globe-alt'),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('visited_at', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('visited_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('visited_at', now()->month)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('visited_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoffeeshopVisitHistories::route('/'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
}
