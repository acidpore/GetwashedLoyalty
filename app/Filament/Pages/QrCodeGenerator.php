<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class QrCodeGenerator extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static string $view = 'filament.pages.qr-code-generator';
    protected static ?string $navigationLabel = 'QR Code Generator';
    protected static ?string $title = 'Generate QR Code';
    protected static ?int $navigationSort = 3;

    public function getCheckinUrl(): string
    {
        return url('/checkin');
    }

    public function getQrCodeSvg(): string
    {
        $url = $this->getCheckinUrl();

        if (!class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" fill="none">
                    <rect width="200" height="200" fill="#f3f4f6"/>
                    <text x="50%%" y="50%%" text-anchor="middle" font-family="sans-serif" font-size="14" fill="#6b7280">
                        Library Missing
                    </text>
                    <text x="50%%" y="65%%" text-anchor="middle" font-family="sans-serif" font-size="10" fill="#ef4444">
                        Run: composer require simplesoftwareio/simple-qrcode
                    </text>
                </svg>'
            );
        }
        
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255)
            ->margin(1)
            ->generate($url);
    }
}
