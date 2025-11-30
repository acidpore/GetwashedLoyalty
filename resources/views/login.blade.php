<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Getwashed Loyalty</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "hsl(202, 100%, 50%)",
                        "background-light": "#f0f2f5",
                        "background-dark": "#1e293b",
                    },
                    fontFamily: {
                        display: ["Poppins", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "1rem",
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
<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
    <div class="flex flex-col min-h-screen p-6" x-data="{ tab: 'customer', phone: '' }">
        <header class="flex-shrink-0">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-white dark:bg-slate-700 dark:text-white rounded-full shadow-sm">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                Kembali
            </a>
        </header>

        <main class="flex-grow flex items-center justify-center">
            <div class="w-full max-w-md bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8 space-y-6">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        <span class="material-symbols-outlined text-3xl text-blue-500 dark:text-blue-400">lock</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Login</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Pilih metode login Anda</p>
                </div>

                <div class="flex bg-slate-100 dark:bg-slate-700/50 rounded-full p-1 text-sm">
                    <button 
                        @click="tab = 'customer'"
                        :class="tab === 'customer' ? 'bg-white dark:bg-slate-600 text-slate-800 dark:text-white font-semibold shadow-sm' : 'text-slate-500 dark:text-slate-400 font-medium'"
                        class="flex-1 py-2 rounded-full transition"
                    >
                        Customer (OTP)
                    </button>
                    <button 
                        @click="tab = 'admin'"
                        :class="tab === 'admin' ? 'bg-white dark:bg-slate-600 text-slate-800 dark:text-white font-semibold shadow-sm' : 'text-slate-500 dark:text-slate-400 font-medium'"
                        class="flex-1 py-2 rounded-full transition"
                    >
                        Admin
                    </button>
                </div>

                @if(session('success'))
                    <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <div x-show="tab === 'customer'" x-cloak>
                    <form method="POST" action="{{ route('login.otp.request') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="whatsapp">Nomor WhatsApp</label>
                            <input 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                id="whatsapp" 
                                name="phone"
                                placeholder="08123456789" 
                                type="tel"
                                required
                                x-model="phone"
                            >
                            <p class="text-xs text-slate-500 dark:text-slate-400">Nomor yang terdaftar saat check-in</p>
                        </div>

                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-slate-800">
                            <span class="material-symbols-outlined text-xl">phonelink_setup</span>
                            Kirim Kode OTP
                        </button>
                    </form>

                    <hr class="border-slate-200 dark:border-slate-700 my-6">

                    <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="phone" x-model="phone">
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="otp">Sudah terima OTP?</label>
                            <input 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-center tracking-[0.5em] text-lg font-semibold focus:ring-green-500 focus:border-green-500" 
                                id="otp" 
                                name="otp_code"
                                maxlength="6" 
                                placeholder="000000" 
                                type="text"
                                required
                            >
                        </div>

                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 dark:focus:ring-offset-slate-800">
                            <span class="material-symbols-outlined text-xl">check</span>
                            Verifikasi &amp; Login
                        </button>
                    </form>
                </div>

                <div x-show="tab === 'admin'" x-cloak>
                    <form method="POST" action="{{ route('login.admin') }}" class="space-y-4">
                        @csrf
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="email">Email</label>
                            <input 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                id="email" 
                                name="email"
                                type="email"
                                placeholder="admin@getwashed.com" 
                                required
                            >
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="password">Password</label>
                            <input 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                id="password" 
                                name="password"
                                type="password"
                                placeholder="••••••••" 
                                required
                            >
                        </div>

                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember" 
                                class="w-4 h-4 text-blue-600 border-slate-300 dark:border-slate-600 rounded focus:ring-blue-500"
                            >
                            <label for="remember" class="ml-2 text-sm text-slate-700 dark:text-slate-300">Remember me</label>
                        </div>

                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-slate-800">
                            <span class="material-symbols-outlined text-xl">key</span>
                            Login Admin
                        </button>
                    </form>
                </div>
            </div>
        </main>

        <footer class="flex-shrink-0 text-center py-4">
            <p class="text-xs text-slate-500 dark:text-slate-400">Belum punya akun? Scan QR di kasir untuk check-in pertama</p>
        </footer>
    </div>
</body>
</html>
