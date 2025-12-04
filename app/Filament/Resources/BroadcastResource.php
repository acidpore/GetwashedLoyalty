<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastResource\Pages;
use App\Models\Broadcast;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Broadcast Messages';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 33;
    protected static ?string $modelLabel = 'Broadcast';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('target_filter')
                    ->label('Target')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'gray',
                        'has_reward' => 'success',
                        'active' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'All Customers',
                        'has_reward' => 'Has Reward',
                        'active' => 'Active',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->numeric()
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->numeric()
                    ->alignCenter()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('estimated_cost')
                    ->label('Cost')
                    ->money('IDR')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'queued' => 'info',
                        'sending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Sent By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'queued' => 'Queued',
                        'sending' => 'Sending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBroadcasts::route('/'),
            'view' => Pages\ViewBroadcast::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
