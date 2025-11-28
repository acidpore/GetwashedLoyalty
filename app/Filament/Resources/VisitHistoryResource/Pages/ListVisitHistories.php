<?php

namespace App\Filament\Resources\VisitHistoryResource\Pages;

use App\Filament\Resources\VisitHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitHistories extends ListRecords
{
    protected static string $resource = VisitHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
