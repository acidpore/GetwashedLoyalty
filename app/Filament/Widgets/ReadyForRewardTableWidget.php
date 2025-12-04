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
        $carwashThreshold = SystemSetting::carwashRewardThreshold();
        $coffeeshopThreshold = SystemSetting::coffeeshopRewardThreshold();
        
        return $table
            ->query(
                Customer::with('user')
                    ->where(function($query) use ($carwashThreshold, $coffeeshopThreshold) {
                        $query->where('carwash_points', '>=', $carwashThreshold)
                              ->orWhere('coffeeshop_points', '>=', $coffeeshopThreshold);
                    })
                    ->orderByDesc('carwash_points')
            )
            ->heading('Customers Ready for Reward')
            ->description('Customers eligible for car wash or coffee shop rewards')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user'),
                
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Phone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),
                
                Tables\Columns\TextColumn::make('carwash_points')
                    ->label('Car Wash')
                    ->badge()
                    ->color(fn ($state) => $state >= $carwashThreshold ? 'success' : 'gray')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('coffeeshop_points')
                    ->label('Coffee Shop')
                    ->badge()
                    ->color(fn ($state) => $state >= $coffeeshopThreshold ? 'success' : 'gray')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('carwash_last_visit_at')
                    ->label('Last Car Wash')
                    ->dateTime('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('coffeeshop_last_visit_at')
                    ->label('Last Coffee')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('carwash_points', 'desc')
            ->striped()
            ->paginated([5, 10, 25])
            ->poll('30s');
    }
}
