<x-layout title="Getwashed Loyalty - Cuci Mobil Pasti Untung" bgClass="bg-slate-50">
    <div class="relative overflow-hidden min-h-screen">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('6874275.jpg') }}" alt="Background" class="w-full h-full object-cover opacity-60">
            <div class="absolute inset-0 bg-gradient-to-b from-white/80 via-white/60 to-blue-50/90"></div>
        </div>

        <!-- Foam/Wave Decoration at Bottom -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none z-0">
            <svg class="relative block w-[calc(100%+1.3px)] h-[150px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-blue-50/50"></path>
            </svg>
        </div>

        <div class="container mx-auto px-4 py-12 relative z-10">
            <!-- Hero Section -->
            <header class="text-center mb-12">
                <div class="inline-flex items-center justify-center p-4 bg-gradient-to-br from-blue-100 to-white rounded-full shadow-lg shadow-blue-100 mb-6 border border-white">
                    <span class="text-5xl filter drop-shadow-sm">🫧</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-black text-slate-800 mb-4 tracking-tight drop-shadow-sm">
                    Getwashed <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-cyan-400">Loyalty</span>
                </h1>
                <p class="text-lg md:text-xl text-slate-500 font-medium max-w-2xl mx-auto">
                    Cuci bersih, poin melimpah! 🚿
                </p>
            </header>

            <!-- Main Action Card -->
            <div class="max-w-md mx-auto bg-white/70 backdrop-blur-xl rounded-[2.5rem] shadow-2xl shadow-blue-200/50 border border-white/80 p-8 mb-16 relative overflow-hidden">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-slate-700">Mulai Kumpulkan Poin</h2>
                    <p class="text-slate-400 text-sm">Scan QR code di kasir sekarang</p>
                </div>

                <div class="space-y-4">
                    <a href="{{ route('checkin') }}" class="group relative block w-full bg-gradient-to-r from-blue-400 to-cyan-400 hover:from-blue-500 hover:to-cyan-500 text-white font-bold py-5 rounded-full text-center shadow-lg shadow-blue-300/50 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
                        <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full transition-transform duration-700 skew-x-12 -translate-x-full"></div>
                        <div class="flex items-center justify-center gap-3">
                            <span class="text-2xl">📱</span>
                            <span>Scan QR Code</span>
                        </div>
                    </a>

                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ url('/admin') }}" class="block w-full bg-white hover:bg-blue-50 text-slate-600 font-bold py-4 rounded-full text-center border border-slate-200 shadow-sm transition-colors">
                                👨‍💻 Admin Dashboard
                            </a>
                        @else
                            <a href="{{ route('customer.dashboard') }}" class="block w-full bg-white hover:bg-blue-50 text-slate-600 font-bold py-4 rounded-full text-center border border-slate-200 shadow-sm transition-colors">
                                👤 Lihat Poin Saya
                            </a>
                        @endif
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-center text-red-400 hover:text-red-500 text-sm font-semibold py-2">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block w-full bg-white hover:bg-blue-50 text-slate-600 font-bold py-4 rounded-full text-center border border-slate-200 shadow-sm transition-colors">
                            🔐 Login Member
                        </a>
                    @endauth
                </div>
            </div>

            <!-- How It Works (Steps) -->
            <div class="mb-16">
                <h3 class="text-center text-xl font-bold text-slate-700 mb-8">Cara Kerjanya</h3>
                <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    @foreach([
                        ['icon' => '📷', 'title' => '1. Scan QR', 'desc' => 'Scan QR code di kasir'],
                        ['icon' => '✍️', 'title' => '2. Isi Data', 'desc' => 'Masukkan nama & WA'],
                        ['icon' => '🎁', 'title' => '3. Dapat Poin', 'desc' => 'Kumpulkan 5 poin!']
                    ] as $step)
                        <div class="bg-white/60 backdrop-blur-sm p-6 rounded-3xl border border-white/60 shadow-lg shadow-blue-50/50 text-center hover:bg-white hover:-translate-y-1 transition-all duration-300">
                            <div class="text-4xl mb-4 bg-gradient-to-br from-blue-50 to-white w-16 h-16 rounded-2xl flex items-center justify-center mx-auto text-blue-500 shadow-inner">
                                {{ $step['icon'] }}
                            </div>
                            <h4 class="font-bold text-slate-800 mb-1">{{ $step['title'] }}</h4>
                            <p class="text-slate-500 text-sm">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Features Grid (Restored) -->
            <div class="max-w-4xl mx-auto">
                <div class="grid md:grid-cols-3 gap-4">
                    @foreach([
                        ['emoji' => '⚡', 'title' => 'Otomatis', 'desc' => 'Poin langsung masuk'],
                        ['emoji' => '📱', 'title' => '100% Digital', 'desc' => 'Tanpa kartu fisik'],
                        ['emoji' => '💎', 'title' => 'Reward Jelas', 'desc' => '5x cuci = 1 diskon']
                    ] as $feature)
                        <div class="bg-blue-50/50 backdrop-blur-sm p-4 rounded-2xl border border-blue-100/50 text-center flex items-center justify-center gap-3 md:block">
                            <div class="text-2xl md:text-3xl md:mb-2">{{ $feature['emoji'] }}</div>
                            <div class="text-left md:text-center">
                                <h5 class="font-bold text-slate-700 text-sm">{{ $feature['title'] }}</h5>
                                <p class="text-slate-500 text-xs">{{ $feature['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <footer class="text-center mt-16 text-slate-400 text-sm font-medium">
                <p>&copy; 2025 Getwashed Loyalty. <br class="md:hidden">Fresh & Clean Experience. 💧</p>
            </footer>
        </div>
    </div>

    <style>
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
</x-layout>
