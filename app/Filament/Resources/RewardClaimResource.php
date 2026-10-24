<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RewardClaimResource\Pages;
use App\Models\RewardClaim;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RewardClaimResource extends Resource
{
    protected static ?string $model = RewardClaim::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Riwayat Klaim';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 62;
    protected static ?string $modelLabel = 'Klaim Hadiah';
    protected static ?string $pluralModelLabel = 'Riwayat Klaim Hadiah';

    public const PROGRAMS = [
        'carwash' => 'Cuci Mobil',
        'motorwash' => 'Cuci Motor',
        'coffeeshop' => 'Coffee Shop',
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer.user', 'claimedBy']))
            ->columns([
                Tables\Columns\ViewColumn::make('mobile_summary')
                    ->label('Klaim')
                    ->view('filament.tables.reward-claim-mobile')
                    ->hiddenFrom('md'),

                Tables\Columns\TextColumn::make('claimed_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('customer.user.name')
                    ->label('Customer')
                    ->description(fn (RewardClaim $record) => $record->customer?->user?->phone)
                    ->searchable(['name', 'phone'])
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('loyalty_type')
                    ->label('Program')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::PROGRAMS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'carwash' => 'info',
                        'motorwash' => 'warning',
                        default => 'success',
                    })
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('reward')
                    ->label('Hadiah')
                    ->description(fn (RewardClaim $record) => "{$record->points_used} poin")
                    ->wrap()
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('claimedBy.name')
                    ->label('Kasir')
                    ->placeholder('-')
                    ->visibleFrom('md'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_type')
                    ->label('Program')
                    ->options(self::PROGRAMS),

                Tables\Filters\Filter::make('claimed_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('claimed_at', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('claimed_at', '<=', $date))),
            ])
            ->defaultSort('claimed_at', 'desc')
            ->defaultPaginationPageOption(25);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRewardClaims::route('/'),
        ];
    }

    // Claims are an audit log: written only by Customer::claimReward().
    public static function canCreate(): bool
    {
        return false;
    }
}
