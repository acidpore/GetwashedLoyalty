<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In - Getwashed Loyalty</title>
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
                        "button-primary": "#059669",
                        "info-bg-light": "#e0e8f9",
                        "info-bg-dark": "#2d3748",
                        "info-text-light": "#4285F4",
                        "info-text-dark": "#90cdf4"
                    },
                    fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "1rem",
                        "lg": "1.25rem",
                        "xl": "1.5rem",
                        "2xl": "2rem",
                    },
                },
            },
        };
    </script>
    <style>
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    <div class="min-h-screen flex flex-col p-4">
        <header class="w-full">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-card-light dark:bg-card-dark rounded-lg shadow-sm">
                <span class="material-symbols-outlined text-primary text-xl">arrow_back</span>
                <span class="text-text-light dark:text-text-dark font-medium">Kembali</span>
            </a>
        </header>

        <main class="flex-grow flex items-center justify-center">
            <div class="w-full max-w-md bg-card-light dark:bg-card-dark rounded-2xl p-6 md:p-8 shadow-lg">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-primary rounded-xl flex items-center justify-center mb-6 shadow-md">
                        <span class="material-symbols-outlined text-white text-4xl">check_circle</span>
                    </div>
                    <h1 class="text-2xl font-bold text-text-light dark:text-text-dark">Check-In Sekarang</h1>
                    
                    <div class="mt-3 mb-4 flex flex-wrap gap-2 justify-center">
                        @foreach($loyaltyTypes as $type)
                            @if($type === 'carwash')
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium">
                                    🚗 Cuci Mobil
                                </span>
                            @elseif($type === 'motorwash')
                                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-sm font-medium">
                                    🏍️ Cuci Motor
                                </span>
                            @elseif($type === 'coffeeshop')
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-lg text-sm font-medium">
                                    ☕ Coffee Shop
                                </span>
                            @endif
                        @endforeach
                    </div>

                    @if(count($loyaltyTypes) > 1)
                        <div class="flex items-center gap-2 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-medium">
                            🎁 {{ count($loyaltyTypes) }}x Poin!
                        </div>
                    @endif
                </div>

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('checkin.store') }}" class="space-y-6 mt-6">
                    @csrf
                    @foreach($loyaltyTypes as $type)
                        <input type="hidden" name="loyalty_types[]" value="{{ $type }}">
                    @endforeach
                    @if($qrCode)
                        <input type="hidden" name="qr_code" value="{{ $qrCode->code }}">
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2" for="full-name">Nama Lengkap</label>
                        <input 
                            class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-border-light dark:border-border-dark rounded-lg focus:ring-primary focus:border-primary placeholder-zinc-400 dark:placeholder-zinc-500 text-text-light dark:text-text-dark" 
                            id="full-name" 
                            name="name" 
                            placeholder="Masukkan nama Anda" 
                            type="text"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-light dark:text-text-dark mb-2" for="whatsapp-number">Nomor WhatsApp</label>
                        <input 
                            class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-border-light dark:border-border-dark rounded-lg focus:ring-primary focus:border-primary placeholder-zinc-400 dark:placeholder-zinc-500 text-text-light dark:text-text-dark" 
                            id="whatsapp-number" 
                            name="phone" 
                            placeholder="08123456789" 
                            type="tel"
                            required
                        >
                        <p class="text-xs text-subtext-light dark:text-subtext-dark mt-2">Format: 08xxx atau 628xxx</p>
                    </div>

                    <button 
                        class="w-full flex items-center justify-center gap-2 bg-button-primary text-white font-semibold py-3 px-4 rounded-lg shadow-md hover:bg-emerald-700 transition-colors" 
                        type="submit"
                    >
                        <span class="material-symbols-outlined">auto_awesome</span>
                        Dapatkan Poin Sekarang!
                    </button>
                </form>

                <div class="mt-6 flex items-start gap-3 p-4 bg-info-bg-light dark:bg-info-bg-dark rounded-lg">
                    <span class="material-symbols-outlined text-info-text-light dark:text-info-text-dark mt-0.5">info</span>
                    <p class="text-sm text-info-text-light dark:text-info-text-dark">Poin akan langsung masuk dan notifikasi + link dashboard dikirim ke WhatsApp Anda</p>
                </div>
            </div>
        </main>

        <footer class="text-center py-4">
            <p class="text-xs text-subtext-light dark:text-subtext-dark">Data Anda aman dan hanya digunakan untuk program loyalitas</p>
        </footer>
    </div>
</body>
</html>
