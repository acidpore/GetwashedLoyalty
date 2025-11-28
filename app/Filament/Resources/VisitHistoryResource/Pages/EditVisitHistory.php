<?php

namespace App\Filament\Resources\VisitHistoryResource\Pages;

use App\Filament\Resources\VisitHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitHistory extends EditRecord
{
    protected static string $resource = VisitHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
