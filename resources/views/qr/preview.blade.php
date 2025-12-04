<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">QR Code Preview</h1>
            <p class="text-gray-600">{{ $qrCode->name ?? 'QR Code' }}</p>
            @if($qrCode->location)
                <p class="text-sm text-gray-500">{{ $qrCode->location }}</p>
            @endif
        </div>

        <div class="bg-gray-50 rounded-lg p-8 mb-6 text-center">
            <div class="inline-block bg-white p-4 rounded-lg shadow">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(400)->errorCorrection('H')->generate($qrCode->url) !!}
            </div>
        </div>

        <div class="space-y-3 mb-6">
            <div class="flex justify-between py-2 border-b">
                <span class="font-medium text-gray-700">Type:</span>
                <span class="text-gray-900">{{ $qrCode->getLoyaltyTypeLabel() }}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-medium text-gray-700">Code:</span>
                <span class="font-mono text-sm text-gray-900">{{ $qrCode->code }}</span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-medium text-gray-700">Status:</span>
                <span class="px-2 py-1 rounded text-sm {{ $qrCode->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $qrCode->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="flex justify-between py-2 border-b">
                <span class="font-medium text-gray-700">Scans:</span>
                <span class="text-gray-900">{{ $qrCode->scan_count }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="font-medium text-gray-700">URL:</span>
                <a href="{{ $qrCode->url }}" target="_blank" class="text-blue-600 hover:underline text-sm truncate max-w-xs">
                    {{ $qrCode->url }}
                </a>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('qr.download', $qrCode->code) }}" 
               class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-center">
                Download SVG
            </a>
            <button onclick="window.print()" 
                    class="flex-1 bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                Print
            </button>
        </div>
    </div>
</body>
</html>
