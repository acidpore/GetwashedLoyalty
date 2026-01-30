<x-layout.layout title="Dashboard - Getwashed Loyalty">
    <div class="min-h-screen bg-[#1C1C1E] flex flex-col">
        
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-[#1C1C1E] px-4 py-4">
            <div class="max-w-md mx-auto flex items-center justify-between">
                <a href="{{ route('home') }}" class="size-10 bg-[#2196F3] rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-lg">chevron_left</span>
                </a>
                <h1 class="text-white font-bold text-lg">My Loyalty</h1>
                <div class="size-10"></div>
            </div>
        </header>

        <main class="flex-1 px-4 pb-24">
            <div class="max-w-md mx-auto">
            
            <!-- Welcome Text -->
            <div class="mb-6">
                <p class="text-white/50 text-sm mb-1">Welcome back,</p>
                <h2 class="text-white text-2xl font-bold">{{ $user->name }}</h2>
            </div>

            <!-- Tab Button -->
            <div class="flex gap-3 mb-6">
                <span class="bg-[#2196F3] text-white px-5 py-2 rounded-full text-sm font-bold">All Programs</span>
            </div>

            <!-- Loyalty Programs List -->
            <div class="space-y-3 mb-8">
                @foreach($loyaltyPrograms as $program)
                @php
                    $iconBg = match($program['type']) {
                        'carwash' => 'bg-[#2196F3]',
                        'motorwash' => 'bg-[#FF9800]',
                        'coffeeshop' => 'bg-[#4CAF50]',
                        default => 'bg-[#2196F3]'
                    };
                    $hasReward = $program['has_reward'];
                @endphp
                <div class="bg-[#2C2C2E] rounded-2xl p-4 flex items-center gap-4 {{ $hasReward ? 'ring-2 ring-[#4CAF50]' : '' }}">
                    <!-- Icon -->
                    <div class="size-12 {{ $iconBg }} rounded-xl flex items-center justify-center p-2 shrink-0">
                        @if($program['icon'] === 'car')
                            <img src="{{ asset('carwash.png') }}" alt="Carwash" class="w-full h-full object-contain">
                        @elseif($program['icon'] === 'motorcycle')
                            <img src="{{ asset('motorcycle.png') }}" alt="Motorwash" class="w-full h-full object-contain">
                        @else
                            <img src="{{ asset('drink.png') }}" alt="Coffee" class="w-full h-full object-contain">
                        @endif
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-white font-bold text-base">{{ $program['name'] }}</h3>
                            @if($hasReward)
                                <span class="material-symbols-outlined text-[#4CAF50] text-lg">check_circle</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-white/80 text-sm font-semibold">{{ $program['points'] }}/{{ $program['threshold'] }} Poin</span>
                            @if($hasReward)
                                <span class="text-[#4CAF50] text-xs font-bold">REWARD READY!</span>
                            @endif
                        </div>
                        <!-- Mini Progress Bar -->
                        <div class="w-full bg-[#1C1C1E] rounded-full h-1.5 mt-2">
                            <div class="h-full {{ $iconBg }} rounded-full" style="width: {{ min(100, ($program['points'] / $program['threshold']) * 100) }}%"></div>
                        </div>
                    </div>
                    

                </div>
                @endforeach
            </div>

            <!-- Stats Cards -->
            @php
                $totalVisits = $customer->carwash_total_visits + $customer->motorwash_total_visits + $customer->coffeeshop_total_visits;
                $lastVisits = collect([
                    $customer->carwash_last_visit_at,
                    $customer->motorwash_last_visit_at,
                    $customer->coffeeshop_last_visit_at,
                ])->filter()->max();
            @endphp
            <div class="grid grid-cols-2 gap-3 mb-8">
                <div class="bg-[#2C2C2E] rounded-2xl p-4 text-center">
                    <div class="text-3xl font-bold text-[#2196F3]">{{ $totalVisits }}</div>
                    <div class="text-white/40 text-xs font-medium mt-1">Total Kunjungan</div>
                </div>
                <div class="bg-[#2C2C2E] rounded-2xl p-4 text-center">
                    <div class="text-sm font-bold text-[#4CAF50]">{{ $lastVisits ? $lastVisits->diffForHumans() : '-' }}</div>
                    <div class="text-white/40 text-xs font-medium mt-1">Terakhir Kunjungan</div>
                </div>
            </div>

            <!-- Visit History -->
            <div class="mb-6">
                <h3 class="text-white font-bold text-base mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-white/50">history</span>
                    Riwayat
                </h3>
                
                <div class="space-y-2">
                    @forelse($recentVisits as $visit)
                        <div class="bg-[#2C2C2E] rounded-xl p-3 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="size-9 bg-[#3A3A3C] rounded-lg flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white/60 text-sm">calendar_today</span>
                                </div>
                                <div>
                                    <div class="text-white text-sm font-semibold">{{ $visit->visited_at->format('d M Y') }}</div>
                                    <div class="text-white/40 text-xs">{{ $visit->visited_at->format('H:i') }} WIB</div>
                                </div>
                            </div>
                            <span class="bg-[#4CAF50] text-white px-3 py-1 rounded-full text-xs font-bold">+{{ $visit->points_earned }}</span>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <span class="material-symbols-outlined text-white/20 text-4xl mb-2">event_busy</span>
                            <p class="text-white/30 text-sm">Belum ada riwayat</p>
                        </div>
                    @endforelse
                </div>
            </div>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 bg-[#1C1C1E] border-t border-white/5 px-6 py-4">
            <div class="max-w-md mx-auto flex items-center justify-around">
                <a href="{{ route('home') }}" class="text-white/40 hover:text-white flex flex-col items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-xl">home</span>
                    <span class="text-xs">Home</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-white/40 hover:text-white flex flex-col items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-xl">logout</span>
                        <span class="text-xs">Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</x-layout.layout>
