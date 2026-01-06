<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Getwashed Loyalty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#1d91f4",
                        "background-light": "#f0f7ff",
                        "background-dark": "#0a1929",
                    },
                    fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "1.5rem",
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
    
    <section class="relative min-h-screen w-full max-w-md mx-auto flex flex-col items-center justify-between overflow-hidden">
        <div class="absolute -top-40 -left-40 w-80 h-80 bg-primary/20 dark:bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/4 -right-20 w-60 h-60 bg-blue-300/20 dark:bg-blue-300/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 -left-20 w-52 h-52 bg-primary/10 dark:bg-primary/5 rounded-full blur-3xl"></div>
        
        <div class="w-full flex flex-col items-center pt-20 px-6 z-10 text-center flex-grow justify-center">
            <div class="mb-6 w-24 h-24 flex items-center justify-center bg-white/50 dark:bg-slate-800/50 rounded-full shadow-lg backdrop-blur-sm">
                <div class="w-20 h-20 flex items-center justify-center bg-white/70 dark:bg-slate-700/70 rounded-full">
                    <span class="material-icons-round text-primary" style="font-size: 48px;">local_car_wash</span>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-slate-800 dark:text-slate-100">Getwashed</h1>
            <h2 class="text-5xl font-extrabold text-primary -mt-2">Loyalty</h2>
            <p class="mt-4 text-slate-600 dark:text-slate-400">
                Cuci bersih, poin melimpah. 
                <span class="font-semibold text-primary">Lebih hemat,</span>
                <span class="font-semibold text-primary">lebih kilap.</span>
            </p>
        </div>
        
        <div class="w-full px-6 pb-8 z-10">
            <div class="bg-white/50 dark:bg-slate-800/40 p-8 rounded-3xl shadow-lg backdrop-blur-md text-center">
                <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100">Mulai Kumpulkan Poin</h3>
                <p class="mt-1 text-slate-500 dark:text-slate-400 text-sm">Scan QR code di kasir sekarang</p>
                
                @auth
                    @if(auth()->user()->isAdmin())
                        <a class="mt-6 w-full inline-flex items-center justify-center px-6 py-3 border-2 border-primary text-primary font-semibold rounded-full hover:bg-primary/10 transition-colors duration-300" href="{{ url('/admin') }}">
                            Admin Dashboard
                            <span class="material-icons-round ml-2">arrow_forward</span>
                        </a>
                    @else
                        <a class="mt-6 w-full inline-flex items-center justify-center px-6 py-3 border-2 border-primary text-primary font-semibold rounded-full hover:bg-primary/10 transition-colors duration-300" href="{{ route('customer.dashboard') }}">
                            Lihat Poin Saya
                            <span class="material-icons-round ml-2">arrow_forward</span>
                        </a>
                    @endif
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mt-4 inline-block text-red-500 dark:text-red-400 font-semibold hover:underline">
                            Logout
                        </button>
                    </form>
                @else
                    <a class="mt-6 w-full inline-flex items-center justify-center px-6 py-3 border-2 border-primary text-primary font-semibold rounded-full hover:bg-primary/10 transition-colors duration-300" href="{{ route('login') }}">
                        Login Member
                        <span class="material-icons-round ml-2">arrow_forward</span>
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <section class="relative min-h-screen w-full max-w-md mx-auto flex flex-col items-center justify-center overflow-hidden px-6 py-12">
        <div class="absolute top-10 -right-40 w-72 h-72 bg-blue-400/20 dark:bg-blue-400/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 -left-32 w-64 h-64 bg-primary/15 dark:bg-primary/8 rounded-full blur-3xl"></div>
        
        <div class="w-full z-10">
            <h3 class="text-3xl font-bold text-slate-800 dark:text-slate-100 text-center mb-12">Cara Kerjanya</h3>
            <div class="space-y-6">
                @foreach([
                    ['number' => '01', 'title' => 'Scan QR', 'desc' => 'Scan kode QR yang tersedia di kasir'],
                    ['number' => '02', 'title' => 'Isi Data', 'desc' => 'Lengkapi informasi nama dan nomor WhatsApp'],
                    ['number' => '03', 'title' => 'Dapatkan Poin', 'desc' => 'Kumpulkan poin dan raih reward menarik']
                ] as $step)
                    <div class="bg-white/40 dark:bg-slate-800/30 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-white/30 dark:border-slate-700/50">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-primary/20 dark:bg-primary/30 flex items-center justify-center">
                                <span class="text-xl font-bold text-primary">{{ $step['number'] }}</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-1">{{ $step['title'] }}</h4>
                                <p class="text-slate-600 dark:text-slate-400 text-sm">{{ $step['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative min-h-screen w-full max-w-md mx-auto flex flex-col items-center justify-center overflow-hidden px-6 py-12">
        <div class="absolute -top-20 -left-40 w-80 h-80 bg-primary/20 dark:bg-primary/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-32 -right-28 w-72 h-72 bg-blue-300/20 dark:bg-blue-300/10 rounded-full blur-3xl"></div>
        
        <div class="w-full z-10">
            <h3 class="text-3xl font-bold text-slate-800 dark:text-slate-100 text-center mb-12">Keunggulan Program</h3>
            <div class="space-y-6">
                @foreach([
                    ['icon' => 'flash_on', 'title' => 'Otomatis', 'desc' => 'Poin terakumulasi secara real-time'],
                    ['icon' => 'phone_android', 'title' => '100% Digital', 'desc' => 'Tidak memerlukan kartu fisik'],
                    ['icon' => 'card_giftcard', 'title' => 'Reward Transparan', 'desc' => 'Benefit jelas setiap 5 kunjungan']
                ] as $feature)
                    <div class="bg-white/40 dark:bg-slate-800/30 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-white/30 dark:border-slate-700/50">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-14 h-14 rounded-xl bg-primary/20 dark:bg-primary/30 flex items-center justify-center">
                                <span class="material-icons-round text-primary text-3xl">{{ $feature['icon'] }}</span>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-1">{{ $feature['title'] }}</h4>
                                <p class="text-slate-600 dark:text-slate-400 text-sm">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <footer class="w-full mt-16 bg-white/40 dark:bg-slate-800/30 backdrop-blur-md p-6 rounded-2xl shadow-sm border border-white/30 dark:border-slate-700/50">
                <div class="text-center">
                    <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4">PT MITRA ANAK CAWANG</h4>
                    
                    <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                        <div class="flex flex-col items-center gap-1">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Alamat</span>
                            <p class="leading-relaxed">
                                Jl. Dewi Sartika No. 184,<br>
                                Kelurahan Cawang, Kecamatan Kramatjati,<br>
                                Kota Administrasi Jakarta Timur,<br>
                                DKI Jakarta 13630, Indonesia
                            </p>
                        </div>
                        
                        <div class="flex flex-col items-center gap-1 pt-2">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Telepon Bisnis</span>
                            <a href="tel:+6285883814652" class="text-primary hover:underline">+6285883814652</a>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <p class="text-slate-500 dark:text-slate-400 text-xs">&copy; 2025 Getwashed Loyalty. Fresh & Clean Experience.</p>
                    </div>
                </div>
            </footer>
        </div>
    </section>

</body>
</html>
