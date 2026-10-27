<?php

namespace App\Filament\Kasir\Resources\PromoResource;

use App\Filament\Kasir\Resources\PromoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePromos extends ManageRecords
{
    protected static string $resource = PromoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Tambah promo')];
    }
}
