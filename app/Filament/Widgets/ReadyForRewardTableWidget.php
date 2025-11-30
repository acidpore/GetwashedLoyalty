<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\SystemSetting;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ReadyForRewardTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $threshold = SystemSetting::rewardPointsThreshold();
        
        return $table
            ->query(
                Customer::with('user')
                    ->where('current_points', '>=', $threshold)
                    ->orderBy('current_points', 'desc')
                    ->orderByDesc('last_visit_at')
            )
            ->heading('Customers Ready for Reward')
            ->description("Customers with {$threshold}+ points eligible for discount")
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user'),
                
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone Number')
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => 
                        '+' . substr($state, 0, 2) . ' ' . substr($state, 2, 3) . '-' . 
                        substr($state, 5, 4) . '-' . substr($state, 9)
                    )
                    ->icon('heroicon-o-phone'),
                
                Tables\Columns\TextColumn::make('current_points')
                    ->label('Points')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('total_visits')
                    ->label('Total Visits')
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-o-calendar-days'),
                
                Tables\Columns\TextColumn::make('last_visit_at')
                    ->label('Last Visit')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock'),
            ])
            ->defaultSort('current_points', 'desc')
            ->striped()
            ->paginated([5, 10, 25])
            ->poll('30s');
    }
}
