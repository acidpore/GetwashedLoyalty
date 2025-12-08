<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use App\Models\Customer;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            Actions\Action::make('reset_carwash')
                ->label('Reset Car Wash')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (Customer $record) => $record->resetPoints('carwash'))
                ->visible(fn (Customer $record) => $record->carwash_points > 0),
            
            Actions\Action::make('reset_coffeeshop')
                ->label('Reset Coffee Shop')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (Customer $record) => $record->resetPoints('coffeeshop'))
                ->visible(fn (Customer $record) => $record->coffeeshop_points > 0),

            Actions\Action::make('reset_motorwash')
                ->label('Reset Motor Wash')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (Customer $record) => $record->resetPoints('motorwash'))
                ->visible(fn (Customer $record) => $record->motorwash_points > 0),
        ];
    }
}
