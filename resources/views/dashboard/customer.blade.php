<x-layout.layout title="Dashboard - Getwashed Loyalty">
    <!-- Navbar -->
    <header class="fixed top-0 left-0 right-0 z-50 transition-colors duration-300 bg-background-dark/80 backdrop-blur-md border-b border-white/5">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="size-6 text-white">
                    <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" d="M24 4H42V17.3333V30.6667H24V44H6V30.6667V17.3333H24V4Z" fill="currentColor" fill-rule="evenodd"></path>
                    </svg>
                </div>
                <h1 class="text-lg font-bold text-white tracking-tight">Getwashed Loyalty</h1>
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                    <span class="material-symbols-outlined text-lg">logout</span>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </form>
        </div>
    </header>

    <main class="pt-28 pb-12 px-6">
        <div class="max-w-5xl mx-auto space-y-8">
            
            <!-- Hero Gradient Card -->
            <div class="relative overflow-hidden rounded-3xl p-8 sm:p-10">
                <div class="absolute inset-0 bg-gradient-to-br from-primary to-blue-600 opacity-90"></div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
                
                <div class="relative z-10 text-white">
                    <div class="flex items-center gap-3 mb-2 opacity-80">
                        <span class="material-symbols-outlined text-2xl">waving_hand</span>
                        <span class="text-sm font-bold uppercase tracking-wider">Welcome Back</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold mb-2">{{ $user->name }}</h2>
                </div>
            </div>

            <!-- Loyalty Programs Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach($loyaltyPrograms as $program)
                <div class="bg-card-dark border border-white/5 rounded-2xl p-6 relative overflow-hidden group hover:border-white/10 transition-all duration-300">
                    <!-- Glow Effect -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br {{ $program['gradient'] }} opacity-5 rounded-full blur-2xl group-hover:opacity-10 transition-opacity"></div>

                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div class="size-12 rounded-xl bg-gradient-to-br {{ $program['gradient'] }} flex items-center justify-center text-white shadow-lg shadow-black/20">
                                @if($program['icon'] === 'car')
                                    <span class="material-symbols-outlined">directions_car</span>
                                @elseif($program['icon'] === 'motorcycle')
                                    <span class="material-symbols-outlined">two_wheeler</span>
                                @else
                                    <span class="material-symbols-outlined">coffee</span>
                                @endif
                            </div>
                            
                            @if($program['has_reward'])
                                <span class="bg-green-500/20 text-green-400 border border-green-500/20 text-[10px] font-bold px-3 py-1 rounded-full flex items-center gap-1 animate-pulse">
                                    <span class="material-symbols-outlined text-sm">redeem</span>
                                    REWARD READY
                                </span>
                            @endif
                        </div>

                        <div class="mb-5">
                            <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">{{ $program['name'] }}</h3>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-extrabold text-white">{{ $program['points'] }}</span>
                                <span class="text-slate-500 text-sm font-medium">/ {{ $program['threshold'] }} Poin</span>
                            </div>
                        </div>

                        <!-- Progress Bar (Themed) -->
                        <div class="w-full bg-background-dark rounded-full h-2.5 mb-2 overflow-hidden border border-white/5">
                            <div class="h-full bg-gradient-to-r {{ $program['gradient'] }} rounded-full transition-all duration-1000 ease-out relative"
                                 style="width: {{ min(100, ($program['points'] / $program['threshold']) * 100) }}%">
                                <div class="absolute top-0 left-0 right-0 h-[1px] bg-white/30"></div>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 font-medium">
                            @if($program['has_reward'])
                                <span class="text-green-400">{{ $program['message'] }}</span>
                            @else
                                Kurang <span class="text-slate-300">{{ max(0, $program['threshold'] - $program['points']) }} poin</span> lagi untuk reward
                            @endif
                        </p>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-card-dark border border-white/5 rounded-2xl p-5 text-center hover:bg-white/[0.02] transition-colors">
                    <div class="text-3xl font-extrabold text-primary mb-1">{{ $customer->total_visits }}</div>
                    <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Kunjungan</div>
                </div>
                <div class="bg-card-dark border border-white/5 rounded-2xl p-5 text-center hover:bg-white/[0.02] transition-colors">
                    <div class="text-sm font-bold text-green-400 mb-2">
                        {{ $customer->last_visit_at ? $customer->last_visit_at->diffForHumans() : '-' }}
                    </div>
                    <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Kunjungan Terakhir</div>
                </div>
            </div>

            <!-- Recent History -->
            <div class="bg-card-dark/50 border border-white/5 rounded-3xl p-6 sm:p-8 backdrop-blur-sm">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400">history</span>
                    Riwayat Kunjungan
                </h3>

                <div class="space-y-4">
                    @forelse($recentVisits as $visit)
                        <div class="flex items-center justify-between p-4 bg-background-dark border border-white/5 rounded-2xl hover:border-white/10 transition-all">
                            <div>
                                <div class="font-bold text-slate-200 text-sm mb-0.5">{{ $visit->visited_at->format('d M Y') }}</div>
                                <div class="text-xs text-slate-500 font-medium">{{ $visit->visited_at->format('H:i') }} WIB</div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1 bg-green-500/10 text-green-400 border border-green-500/20 px-2.5 py-1 rounded-lg text-xs font-bold">
                                    +{{ $visit->points_earned }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <div class="size-16 bg-background-dark rounded-full flex items-center justify-center mx-auto mb-4 border border-white/5">
                                <span class="material-symbols-outlined text-slate-600 text-2xl">event_busy</span>
                            </div>
                            <p class="text-slate-500 text-sm">Belum ada riwayat kunjungan</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3 pt-4">
                <a href="{{ route('home') }}" class="block w-full bg-transparent hover:bg-white/5 text-slate-400 hover:text-white font-bold py-4 rounded-xl text-center transition-all border border-white/10 transform active:scale-[0.98]">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </main>

    <!-- Simple Footer -->
    <footer class="py-8 text-center border-t border-white/5 mt-auto">
        <p class="text-xs text-slate-600">© 2026 Getwashed x Latte</p>
    </footer>
</x-layout.layout>
