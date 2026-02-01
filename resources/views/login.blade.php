<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Getwashed x Latte</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-dark": "#101922",
                        "card-dark": "#16222e",
                    },
                    fontFamily: {
                        "display": ["Manrope", "sans-serif"]
                    }
                },
            },
        }
    </script>
</head>
<body class="font-display bg-background-dark text-slate-200 h-screen w-full flex overflow-hidden" x-data="{ tab: 'customer' }">

    <!-- Section 1: Left Visual -->
    <div class="hidden md:flex md:w-1/2 lg:w-[55%] relative items-center justify-center text-center text-white bg-cover bg-center px-4" 
         style='background-image: linear-gradient(rgba(16, 25, 34, 0.6) 0%, rgba(16, 25, 34, 0.8) 100%), url("https://lh3.googleusercontent.com/aida-public/AB6AXuAfFN4EoCQFwDCt57D57wkXs0tzCyQo46MQVGeegeRjEmbssWVAGgZW6iXTUEcRAvhf6AqnP3VJkCvKNpk-Iav50HJdj-YtsZHDiYTb6xdNxE1SOeHyrSakSLv4kTypEliCGbrbhJqk5P0C9VzA_AkEL1xNCwrz-RBM2Nhf8JlptluCvC9fx84xCuzANajJU-ZKtQb42gNqTD9CVxAgikG0-1Rcaeu10db4LHLxIC3LqCYKcihcDuM_QBFCHJIaWngs72ENTy3TNvU");'>
        
        <!-- Back Button (Desktop Left) -->
        <div class="absolute top-8 left-8 z-10 w-fit">
            <a href="/" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white/80 hover:text-white transition-all text-sm font-medium border border-white/10 backdrop-blur-md group">
                <span class="material-symbols-outlined text-sm transition-transform group-hover:-translate-x-0.5">arrow_back</span>
                <span class="text-xs">Kembali ke Beranda</span>
            </a>
        </div>

        <div class="flex flex-col gap-4">
            <h1 class="text-4xl sm:text-5xl md:text-7xl font-black leading-tight tracking-tighter">Getwashed x Latte</h1>
            <p class="text-base sm:text-lg md:text-xl font-normal leading-normal">Your Premium Stop for Coffee & Care</p>
        </div>
    </div>

    <!-- Section 2: Right Login Modal -->
    <div class="w-full md:w-1/2 lg:w-[45%] h-full flex flex-col relative bg-background-dark/95 backdrop-blur-3xl border-l border-white/5 shadow-2xl">
        
        <!-- Back Button (Mobile Only) -->
        <div class="absolute top-6 right-6 z-10 md:hidden">
            <a href="/" class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-all text-sm font-medium border border-white/5 backdrop-blur-md group">
                <span class="text-xs">Kembali</span>
                <span class="material-symbols-outlined text-sm transition-transform group-hover:translate-x-0.5">arrow_forward</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12 overflow-y-auto">
            <div class="w-full max-w-sm space-y-8">
                
                <!-- Header Text -->
                <div class="space-y-2">
                    <h1 class="text-3xl lg:text-4xl font-extrabold text-white tracking-tight">Login Portal</h1>
                    <p class="text-slate-400">Masuk untuk mengelola akun Anda</p>
                </div>

                <!-- Tabs -->
                <div class="flex p-1 bg-card-dark rounded-xl border border-white/5">
                    <button 
                        @click="tab = 'customer'"
                        :class="tab === 'customer' ? 'bg-primary text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                        class="flex-1 py-3 text-sm font-bold rounded-lg transition-all"
                    >
                        Customer
                    </button>
                    <button 
                        @click="tab = 'admin'"
                        :class="tab === 'admin' ? 'bg-primary text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                        class="flex-1 py-3 text-sm font-bold rounded-lg transition-all"
                    >
                        Admin
                    </button>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-xl text-green-400 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">error</span>
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Customer Form (PIN Login) -->
                <div x-show="tab === 'customer'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
                    
                    <form method="POST" action="{{ route('login.pin') }}" class="space-y-5">
                        @csrf
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor WhatsApp</label>
                                <input type="tel" name="phone" required placeholder="08..."
                                       class="w-full bg-card-dark border border-white/10 rounded-xl px-4 py-4 text-white placeholder:text-zinc-600 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">PIN (6 Digit)</label>
                                <input type="password" name="pin" required placeholder="******" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                                       class="w-full bg-card-dark border border-white/10 rounded-xl px-4 py-4 text-white text-center tracking-[0.5em] font-bold placeholder:tracking-normal placeholder:text-zinc-600 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">login</span>
                            Masuk
                        </button>
                    </form>

                    <div class="text-center space-y-2">
                        <p class="text-xs text-slate-500">Belum punya PIN atau lupa PIN?</p>
                        <p class="text-xs text-slate-400">Scan QR Code untuk check-in dan atur PIN dari dashboard.</p>
                    </div>
                </div>

                <!-- Admin Form -->
                <div x-show="tab === 'admin'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                    <form method="POST" action="{{ route('login.admin') }}" class="space-y-5">
                        @csrf
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Email</label>
                                <input type="email" name="email" required placeholder="admin@example.com"
                                       class="w-full bg-card-dark border border-white/10 rounded-xl px-4 py-4 text-white placeholder:text-zinc-600 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Password</label>
                                <input type="password" name="password" required placeholder="********"
                                       class="w-full bg-card-dark border border-white/10 rounded-xl px-4 py-4 text-white placeholder:text-zinc-600 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="remember" class="rounded border-white/10 bg-card-dark text-primary focus:ring-primary/50">
                                <span class="text-sm text-slate-400">Ingat Saya</span>
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">key</span>
                            Login Admin
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Mini Footer -->
        <div class="p-6 text-center">
             <p class="text-xs text-slate-600">© 2026 Getwashed x Latte</p>
        </div>

    </div>

</body>
</html>
