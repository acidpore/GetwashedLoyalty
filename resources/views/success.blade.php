<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In Success - Getwashed Loyalty</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#4285F4",
                        "background-light": "#E6F4EA",
                        "background-dark": "#18181b",
                        "card-light": "#FFFFFF",
                        "card-dark": "#27272a",
                        "text-light": "#3f3f46",
                        "text-dark": "#d4d4d8",
                        "subtext-light": "#71717a",
                        "subtext-dark": "#a1a1aa",
                        "border-light": "#e4e4e7",
                        "border-dark": "#3f3f46",
                    },
                   fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                },
            },
        };
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <div class="min-h-screen flex flex-col p-4">
        <main class="flex-grow flex items-center justify-center">
            <div class="w-full max-w-md space-y-4">
                @php
                    $showReward = $carwashReward || $motorwashReward || $coffeeshopReward;
                    $loyaltyTypesArray = explode(',', request('loyalty_types', ''));
                @endphp

                @if($showReward)
                    <div class="bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl p-8 shadow-2xl text-center text-white">
                        <div class="text-6xl mb-4">🎉 SELAMAT 🎉</div>
                        <h1 class="text-3xl font-bold mb-2">{{ $name }}!</h1>
                        <p class="text-xl mb-6">Kamu Dapat Reward!</p>
                        
                        <div class="space-y-2">
                            @if($carwashReward)
                                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                                    <p class="font-semibold">🚗 DISKON CUCI MOBIL</p>
                                </div>
                            @endif
                            @if($motorwashReward)
                                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                                    <p class="font-semibold">🏍️ DISKON CUCI MOTOR</p>
                                </div>
                            @endif
                            @if($coffeeshopReward)
                                <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3">
                                    <p class="font-semibold">☕ GRATIS KOPI</p>
                                </div>
                            @endif
                        </div>

                        <p class="mt-6 text-sm opacity-90">Tunjukkan pesan WhatsApp ke kasir</p>
                    </div>
                @else
                    <div class="bg-card-light dark:bg-card-dark rounded-2xl p-8 shadow-lg text-center">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-primary rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="material-symbols-outlined text-white text-5xl">done</span>
                        </div>
                        <h1 class="text-2xl font-bold text-text-light dark:text-text-dark mb-2">Check-In Berhasil!</h1>
                        <p class="text-subtext-light dark:text-subtext-dark mb-8">Terima kasih, {{ $name }}</p>
                    </div>
                @endif

                @if(in_array('carwash', $loyaltyTypesArray) || count($loyaltyTypesArray) === 0)
                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-text-light dark:text-text-dark flex items-center gap-2">
                                <span>🚗</span> Cuci Mobil
                            </h3>
                            <span class="text-2xl font-bold text-blue-600">{{ $carwashPoints }}/5</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-3 rounded-full transition-all" style="width: {{ ($carwashPoints / 5) * 100 }}%"></div>
                        </div>
                        @if(!$carwashReward)
                            <p class="text-sm text-subtext-light dark:text-subtext-dark mt-2">{{ 5 - $carwashPoints }} poin lagi untuk reward</p>
                        @endif
                    </div>
                @endif

                @if(in_array('motorwash', $loyaltyTypesArray))
                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-text-light dark:text-text-dark flex items-center gap-2">
                                <span>🏍️</span> Cuci Motor
                            </h3>
                            <span class="text-2xl font-bold text-purple-600">{{ $motorwashPoints }}/5</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-3 rounded-full transition-all" style="width: {{ ($motorwashPoints / 5) * 100 }}%"></div>
                        </div>
                        @if(!$motorwashReward)
                            <p class="text-sm text-subtext-light dark:text-subtext-dark mt-2">{{ 5 - $motorwashPoints }} poin lagi untuk reward</p>
                        @endif
                    </div>
                @endif

                @if(in_array('coffeeshop', $loyaltyTypesArray) || count($loyaltyTypesArray) === 0)
                    <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 shadow">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-text-light dark:text-text-dark flex items-center gap-2">
                                <span>☕</span> Coffee Shop
                            </h3>
                            <span class="text-2xl font-bold text-amber-600">{{ $coffeeshopPoints }}/5</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-amber-400 to-amber-600 h-3 rounded-full transition-all" style="width: {{ ($coffeeshopPoints / 5) * 100 }}%"></div>
                        </div>
                        @if(!$coffeeshopReward)
                            <p class="text-sm text-subtext-light dark:text-subtext-dark mt-2">{{ 5 - $coffeeshopPoints }} poin lagi untuk reward</p>
                        @endif
                    </div>
                @endif

                <div class="flex gap-3">
                    <a href="{{ route('home') }}" class="flex-1 bg-gray-200 dark:bg-gray-700 text-text-light dark:text-text-dark font-semibold py-3 px-4 rounded-lg text-center hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Selesai
                    </a>
                </div>

                <div class="bg-info-bg-light dark:bg-info-bg-dark rounded-lg p-4 text-center">
                    <p class="text-sm text-info-text-light dark:text-info-text-dark">✅ Notifikasi + Link Dashboard telah dikirim ke WhatsApp Anda</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
