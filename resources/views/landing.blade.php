<x-layout.layout title="Getwashed Loyalty - Cuci Mobil Pasti Untung" bgClass="bg-slate-50">
    <div class="relative overflow-hidden min-h-screen">
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('6874275.jpg') }}" alt="Background" class="w-full h-full object-cover opacity-60">
            <div class="absolute inset-0 bg-gradient-to-b from-white/80 via-white/60 to-blue-50/90"></div>
            
            <!-- Bubbles -->
            <div class="bubble w-20 h-20 top-20 left-10 delay-0"></div>
            <div class="bubble w-12 h-12 top-40 right-20 delay-2000"></div>
            <div class="bubble w-16 h-16 bottom-40 left-1/4 delay-4000"></div>
            <div class="bubble w-24 h-24 bottom-20 right-1/3 delay-1000"></div>
            <div class="bubble w-10 h-10 top-1/3 left-1/2 delay-3000"></div>
        </div>

        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none z-0">
            <svg class="relative block w-[calc(100%+1.3px)] h-[150px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-blue-50/50"></path>
            </svg>
        </div>

        <div class="container mx-auto px-4 py-12 relative z-10">
            <header class="text-center mb-12 z-pattern-hero">
                <div class="inline-flex items-center justify-center p-4 bg-white/30 backdrop-blur-md rounded-full shadow-lg shadow-blue-100/50 mb-6 border border-white/50">
                    <svg class="w-12 h-12 text-blue-600 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" opacity="0.2"/>
                        <circle cx="8" cy="8" r="2"/>
                        <circle cx="16" cy="10" r="2.5"/>
                        <circle cx="10" cy="16" r="1.5"/>
                    </svg>
                </div>
                <h1 class="text-5xl md:text-7xl font-extrabold text-slate-800 mb-4 tracking-tight drop-shadow-sm">
                    Getwashed <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">Loyalty</span>
                </h1>
                <p class="text-lg md:text-2xl text-slate-600 font-light max-w-2xl mx-auto leading-relaxed">
                    Cuci bersih, poin melimpah. <span class="font-semibold text-blue-600">Lebih hemat, lebih kilap.</span>
                </p>
            </header>

            <div class="max-w-md mx-auto bg-white/40 backdrop-blur-xl rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/60 p-8 mb-16 relative overflow-hidden z-pattern-cta">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-slate-700">Mulai Kumpulkan Poin</h2>
                    <p class="text-slate-400 text-sm">Scan QR code di kasir sekarang</p>
                </div>

                <div class="space-y-4">

                    @auth
                        @if(auth()->user()->isAdmin())
                            <div class="flex justify-center">
                                <x-buttons.animated-button href="{{ url('/admin') }}" text="Admin Dashboard" />
                            </div>
                        @else
                            <div class="flex justify-center">
                                <x-buttons.animated-button href="{{ route('customer.dashboard') }}" text="Lihat Poin Saya" />
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-center text-red-400 hover:text-red-500 text-sm font-semibold py-2">
                                Logout
                            </button>
                        </form>
                    @else
                        <div class="flex justify-center">
                            <x-buttons.animated-button href="{{ route('login') }}" text="Login Member" />
                        </div>
                    @endauth
                </div>
            </div>

            <div class="mb-20 z-pattern-steps">
                <div class="text-center mb-12">
                    <h3 class="text-2xl md:text-3xl font-semibold text-slate-800 mb-3 tracking-tight">Cara Kerjanya</h3>
                    <p class="text-slate-500 text-sm md:text-base max-w-2xl mx-auto">Tiga langkah mudah untuk mulai mengumpulkan poin loyalitas</p>
                </div>
                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto mobile-z-pattern">
                    @foreach([
                        ['number' => '01', 'title' => 'Scan QR', 'desc' => 'Scan kode QR yang tersedia di kasir'],
                        ['number' => '02', 'title' => 'Isi Data', 'desc' => 'Lengkapi informasi nama dan nomor WhatsApp'],
                        ['number' => '03', 'title' => 'Dapatkan Poin', 'desc' => 'Kumpulkan poin dan raih reward menarik']
                    ] as $step)
                        <div class="bg-white/40 backdrop-blur-md p-8 rounded-2xl border border-white/50 shadow-sm hover:shadow-md transition-all duration-300 z-pattern-item group hover:-translate-y-1">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-white/60 flex items-center justify-center shadow-sm border border-white/50">
                                    <span class="text-lg font-bold text-blue-600 group-hover:scale-110 transition-transform">{{ $step['number'] }}</span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-slate-800 mb-2 tracking-tight">{{ $step['title'] }}</h4>
                                    <p class="text-slate-600 text-sm leading-relaxed font-light">{{ $step['desc'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="max-w-5xl mx-auto mb-16 z-pattern-features">
                <div class="bg-white/30 backdrop-blur-xl rounded-3xl border border-white/40 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-8 md:p-12 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/40 to-transparent pointer-events-none"></div>
                    <div class="relative z-10">
                        <div class="text-center mb-12">
                            <h3 class="text-2xl md:text-3xl font-bold text-slate-800 mb-3 tracking-tight">Keunggulan Program</h3>
                            <p class="text-slate-600 text-sm md:text-base font-light">Sistem loyalitas yang dirancang untuk kemudahan Anda</p>
                        </div>
                    <div class="grid md:grid-cols-3 gap-8 mobile-z-pattern-alt">
                        @foreach([
                            ['icon' => 'lightning', 'title' => 'Otomatis', 'desc' => 'Poin terakumulasi secara real-time'],
                            ['icon' => 'phone', 'title' => '100% Digital', 'desc' => 'Tidak memerlukan kartu fisik'],
                            ['icon' => 'gift', 'title' => 'Reward Transparan', 'desc' => 'Benefit jelas setiap 5 kunjungan']
                        ] as $index => $feature)
                            <div class="text-center z-pattern-item group">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/50 border border-white/60 mb-6 group-hover:border-blue-300 group-hover:bg-blue-50/50 transition-all duration-300 shadow-sm group-hover:shadow-md group-hover:-translate-y-1">
                                    @if($feature['icon'] == 'lightning')
                                        <svg class="w-7 h-7 text-slate-700 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    @elseif($feature['icon'] == 'phone')
                                        <svg class="w-7 h-7 text-slate-700 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <rect x="5" y="2" width="14" height="20" rx="2" stroke-width="1.5"/>
                                            <path d="M12 18h.01" stroke-linecap="round" stroke-width="1.5"/>
                                        </svg>
                                    @else
                                        <svg class="w-7 h-7 text-slate-700 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                        </svg>
                                    @endif
                                </div>
                                <h5 class="text-lg font-bold text-slate-800 mb-2 tracking-tight">{{ $feature['title'] }}</h5>
                                <p class="text-slate-600 text-sm leading-relaxed font-light">{{ $feature['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    </div>
                </div>
            </div>

            <footer class="text-center mt-16 text-slate-400 text-sm font-medium">
                <p>&copy; 2025 Getwashed Loyalty. <br class="md:hidden">Fresh & Clean Experience.</p>
            </footer>
        </div>
    </div>

    <style>
        /* Bubble Animation */
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.5);
            animation: float-bubble 8s infinite ease-in-out;
            z-index: 0;
        }

        @keyframes float-bubble {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
            50% { transform: translateY(-20px) scale(1.1); opacity: 0.6; }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        @media (max-width: 768px) {
            .mobile-z-pattern {
                display: flex;
                flex-direction: column;
                align-items: stretch;
            }
            
            .mobile-z-pattern .z-pattern-item:nth-child(1) {
                align-self: flex-start;
                margin-right: auto;
                width: 85%;
            }
            
            .mobile-z-pattern .z-pattern-item:nth-child(2) {
                align-self: flex-end;
                margin-left: auto;
                width: 85%;
            }
            
            .mobile-z-pattern .z-pattern-item:nth-child(3) {
                align-self: flex-start;
                margin-right: auto;
                width: 85%;
            }

            .mobile-z-pattern-alt {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .mobile-z-pattern-alt .z-pattern-item {
                width: 90%;
                max-width: 320px;
            }

            .z-pattern-hero {
                animation: slideInLeft 0.6s ease-out;
            }
            
            .z-pattern-cta {
                animation: slideInRight 0.8s ease-out;
            }
            
            .z-pattern-steps {
                animation: slideInLeft 1s ease-out;
            }
            
            .z-pattern-features {
                animation: slideInRight 1.2s ease-out;
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</x-layout.layout>
