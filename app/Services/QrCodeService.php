<?php

namespace App\Services;

use App\Models\QrCode;
use Illuminate\Support\Str;

class QrCodeService
{
    public function generate(array $data): QrCode
    {
        $code = $this->generateUniqueCode();

        return QrCode::create([
            'code' => $code,
            'loyalty_type' => $data['loyalty_type'],
            'qr_type' => $data['qr_type'] ?? 'permanent',
            'name' => $data['name'] ?? null,
            'location' => $data['location'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    public function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(10));
        } while (QrCode::where('code', $code)->exists());

        return $code;
    }

    public function incrementScan(string $code): void
    {
        $qrCode = QrCode::where('code', $code)->first();

        if ($qrCode) {
            $qrCode->incrementScan();
        }
    }

    public function validateQrCode(string $code): ?QrCode
    {
        $qrCode = QrCode::where('code', $code)->first();

        if (!$qrCode || !$qrCode->isValid()) {
            return null;
        }

        return $qrCode;
    }
}
