<?php

namespace App\Filament\Kasir\Resources\IngredientResource;

use App\Filament\Kasir\Resources\IngredientResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageIngredients extends ManageRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah bahan')];
    }
}
