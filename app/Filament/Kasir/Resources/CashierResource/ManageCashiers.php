<?php

namespace App\Filament\Kasir\Resources\CashierResource;

use App\Filament\Kasir\Resources\CashierResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCashiers extends ManageRecords
{
    protected static string $resource = CashierResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah kasir')];
    }
}
