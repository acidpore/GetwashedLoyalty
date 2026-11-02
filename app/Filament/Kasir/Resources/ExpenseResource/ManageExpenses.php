<?php

namespace App\Filament\Kasir\Resources\ExpenseResource;

use App\Filament\Kasir\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageExpenses extends ManageRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Catat pengeluaran')
            ->mutateFormDataUsing(fn (array $data) => $data + ['user_id' => auth()->id()])];
    }
}
