<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Atur PIN - Getwashed x Latte</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script>
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
<body class="font-display bg-background-dark text-slate-200 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md space-y-8">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-primary/10 rounded-2xl mb-4">
                <span class="material-symbols-outlined text-4xl text-primary">lock</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                {{ $hasPin ? 'Ganti PIN' : 'Atur PIN' }}
            </h1>
            <p class="text-slate-400">PIN digunakan untuk login ke dashboard</p>
        </div>

        <!-- Alerts -->
        @if(session('info'))
            <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-xl text-blue-400 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">info</span>
                {{ session('info') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">error</span>
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('customer.pin.store') }}" class="space-y-6">
            @csrf
            
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">PIN Baru (6 Digit)</label>
                    <input type="password" name="pin" required placeholder="******" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                           class="w-full bg-card-dark border border-white/10 rounded-xl px-4 py-4 text-white text-center tracking-[0.5em] text-2xl font-bold placeholder:tracking-normal placeholder:text-zinc-600 focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none">
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Konfirmasi PIN</label>
                    <input type="password" name="pin_confirmation" required placeholder="******" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                           class="w-full bg-card-dark border border-white/10 rounded-xl px-4 py-4 text-white text-center tracking-[0.5em] text-2xl font-bold placeholder:tracking-normal placeholder:text-zinc-600 focus:border-green-500 focus:ring-1 focus:ring-green-500 transition-all outline-none">
                </div>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                {{ $hasPin ? 'Simpan PIN Baru' : 'Atur PIN' }}
            </button>
        </form>

        <!-- Skip / Back -->
        <div class="text-center">
            <a href="{{ route('customer.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-300 transition-colors">
                {{ $hasPin ? 'Batal' : 'Lewati untuk sekarang' }}
            </a>
        </div>

        <!-- Info -->
        <div class="bg-card-dark border border-white/5 rounded-xl p-4 space-y-2">
            <p class="text-xs text-slate-500 flex items-start gap-2">
                <span class="material-symbols-outlined text-sm mt-0.5">tips_and_updates</span>
                <span>PIN akan digunakan bersama nomor WhatsApp untuk login. Jika lupa PIN, check-in ulang untuk reset.</span>
            </p>
        </div>

    </div>

</body>
</html>
