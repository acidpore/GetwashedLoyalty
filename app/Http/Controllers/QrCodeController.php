<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function preview(string $code)
    {
        $qrCode = QrCode::where('code', $code)->firstOrFail();
        
        return view('qr.preview', compact('qrCode'));
    }

    public function download(string $code)
    {
        $qrCode = QrCode::where('code', $code)->firstOrFail();
        
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(600)
            ->errorCorrection('H')
            ->generate($qrCode->url);

        return response($qrSvg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', "attachment; filename=qr-{$code}.svg");
    }
}
