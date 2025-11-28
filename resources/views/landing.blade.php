<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Getwashed Loyalty - Program Loyalitas Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-indigo-900 mb-4">
                🚗 Getwashed Loyalty
            </h1>
            <p class="text-lg text-gray-700">
                Kumpulkan poin setiap cuci kendaraan, dapatkan diskon!
            </p>
        </header>

        <!-- Hero Section -->
        <div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-2xl p-8 mb-8">
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">🎁</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    Cara Kerjanya Sangat Mudah!
                </h2>
            </div>

            <!-- Steps -->
            <div class="space-y-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">
                        1
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Scan QR Code</h3>
                        <p class="text-gray-600">Scan QR di kasir setiap kali cuci</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">
                        2
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Isi Nama & No WA</h3>
                        <p class="text-gray-600">Data otomatis tersimpan aman</p>
                    </div>
                </div>

                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-indigo-500 text-white rounded-full flex items-center justify-center font-bold">
                        3
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Kumpulkan 5 Poin</h3>
                        <p class="text-gray-600">Dapatkan notifikasi diskon via WhatsApp!</p>
                    </div>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="mt-8 space-y-3">
                <a href="{{ route('checkin') }}" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl text-center transition">
                    🎯 Scan QR & Dapatkan Poin
                </a>
                
                <a href="{{ route('login') }}" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-xl text-center transition">
                    🔐 Login Customer / Admin
                </a>
            </div>
        </div>

        <!-- Features -->
        <div class="max-w-4xl mx-auto grid md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl mb-3">📱</div>
                <h3 class="font-bold text-gray-800 mb-2">100% Digital</h3>
                <p class="text-gray-600 text-sm">Tanpa kartu fisik, semua via WhatsApp</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl mb-3">⚡</div>
                <h3 class="font-bold text-gray-800 mb-2">Otomatis</h3>
                <p class="text-gray-600 text-sm">Poin langsung masuk tanpa ribet</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg text-center">
                <div class="text-4xl mb-3">🎁</div>
                <h3 class="font-bold text-gray-800 mb-2">Reward Jelas</h3>
                <p class="text-gray-600 text-sm">5x cuci = 1 diskon pasti!</p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="text-center mt-12 text-gray-600 text-sm">
            <p>&copy; 2025 Getwashed Loyalty. Made with ❤️ for better customer experience.</p>
        </footer>
    </div>
</body>
</html>
