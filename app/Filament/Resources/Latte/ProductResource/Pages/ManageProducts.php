<?php

namespace App\Filament\Resources\Latte\ProductResource\Pages;

use App\Filament\Resources\Latte\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah menu')->modalWidth('3xl')];
    }
}
