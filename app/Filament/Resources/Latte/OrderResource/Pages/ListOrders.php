<?php

namespace App\Filament\Resources\Latte\OrderResource\Pages;

use App\Filament\Resources\Latte\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kasir')->label('Buka kasir')->icon('heroicon-o-calculator')
                ->url(fn () => \App\Filament\Kasir\Pages\Pos::getUrl(panel: 'kasir'))->openUrlInNewTab(),
        ];
    }
}
