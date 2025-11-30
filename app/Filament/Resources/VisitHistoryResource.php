<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitHistoryResource\Pages;
use App\Filament\Resources\VisitHistoryResource\RelationManagers;
use App\Models\VisitHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitHistoryResource extends Resource
{
    protected static ?string $model = VisitHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Visit History';

    protected static ?string $modelLabel = 'Visit';

    protected static ?string $pluralModelLabel = 'Visit Histories';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only resource, no form needed
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer.user'))
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
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('visited_at', today())),
                
                Tables\Filters\Filter::make('yesterday')
                    ->label('Yesterday')
                    ->query(fn (Builder $query): Builder => $query->whereDate('visited_at', today()->subDay())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('visited_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('visited_at', now()->month)),
                
                Tables\Filters\Filter::make('created')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visited_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visited_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ])
            ->defaultSort('visited_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->deferLoading();
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
            'index' => Pages\ListVisitHistories::route('/'),
            // No create or edit pages - read-only resource
        ];
    }
    
    // Disable create button
    public static function canCreate(): bool
    {
        return false;
    }
}
