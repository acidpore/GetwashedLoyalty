<?php

namespace App\Filament\Resources\QrCodeResource\Pages;

use App\Filament\Resources\QrCodeResource;
use App\Services\QrCodeService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateQrCode extends CreateRecord
{
    protected static string $resource = QrCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $qrCodeService = app(QrCodeService::class);
        $data['code'] = $qrCodeService->generateUniqueCode();
        $data['created_by'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('QR Code created successfully')
            ->body('You can now view and print the QR code');
    }
}
