<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQrCode extends ViewRecord
{
    protected static string $resource = QrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print QR Code')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn ($record) => route('qr.preview', $record->code))
                ->openUrlInNewTab(),
            
            Actions\Action::make('download')
                ->label('Download SVG')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn ($record) => route('qr.download', $record->code))
                ->openUrlInNewTab(),
            
            Actions\EditAction::make(),
            
            Actions\DeleteAction::make()
                ->successRedirectUrl(route('filament.admin.resources.qr-codes.index')),
        ];
    }
}
