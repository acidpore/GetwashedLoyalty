<x-layout.layout title="Dashboard - Getwashed Loyalty" bg-class="bg-gray-50">
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:text-red-800">Logout</button>
            </form>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-3xl p-8 text-white mb-8">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/>
                </svg>
                <h2 class="text-3xl font-bold">Halo, {{ $user->name }}!</h2>
            </div>
            <p class="text-purple-100">Selamat datang di dashboard loyalitas Anda</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-8">
            @foreach($loyaltyPrograms as $program)
            <div class="bg-white rounded-2xl shadow-lg p-6 relative overflow-hidden">
                <!-- Background Decoration -->
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br {{ $program['gradient'] }} opacity-10 rounded-full blur-2xl"></div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $program['gradient'] }} flex items-center justify-center text-white shadow-lg">
                            @if($program['icon'] === 'car')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            @elseif($program['icon'] === 'motorcycle')
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            @endif
                        </div>
                        @if($program['has_reward'])
                            <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full animate-pulse">
                                REWARD READY
                            </span>
                        @endif
                    </div>

                    <h3 class="text-gray-500 text-sm font-medium mb-1">{{ $program['name'] }}</h3>
                    <div class="flex items-baseline gap-1 mb-4">
                        <span class="text-3xl font-bold {{ $program['has_reward'] ? 'text-green-600' : 'text-gray-800' }}">
                            {{ $program['points'] }}
                        </span>
                        <span class="text-gray-400 text-sm">/ {{ $program['threshold'] }}</span>
                    </div>

                    <div class="w-full bg-gray-100 rounded-full h-2 mb-3 overflow-hidden">
                        <div class="h-full bg-gradient-to-r {{ $program['gradient'] }} transition-all duration-500 ease-out"
                             style="width: {{ min(100, ($program['points'] / $program['threshold']) * 100) }}%">
                        </div>
                    </div>

                    <p class="text-xs text-gray-500">
                        @if($program['has_reward'])
                            {{ $program['message'] }}
                        @else
                            {{ max(0, $program['threshold'] - $program['points']) }} poin lagi untuk reward
                        @endif
                    </p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="max-w-2xl mx-auto">
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-blue-50 p-4 rounded-xl text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $customer->total_visits }}</div>
                    <div class="text-sm text-gray-600">Total Kunjungan</div>
                </div>
                <div class="bg-green-50 p-4 rounded-xl text-center">
                    <div class="text-sm font-semibold text-green-600">
                        {{ $customer->last_visit_at ? $customer->last_visit_at->diffForHumans() : 'Belum pernah' }}
                    </div>
                    <div class="text-sm text-gray-600">Kunjungan Terakhir</div>
                </div>
            </div>

        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Riwayat Kunjungan</span>
            </h3>

            @forelse($recentVisits as $visit)
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-3">
                    <div>
                        <div class="font-semibold text-gray-800">{{ $visit->visited_at->format('d M Y') }}</div>
                        <div class="text-sm text-gray-600">{{ $visit->visited_at->format('H:i') }} WIB</div>
                    </div>
                    <div class="text-right">
                        <div class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold">
                            +{{ $visit->points_earned }} poin
                        </div>
                        <div class="text-xs text-gray-500 mt-1">{{ $visit->visited_at->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p>Belum ada riwayat kunjungan</p>
                </div>
            @endforelse
        </div>

            <div class="space-y-3">
                <x-buttons.link-button href="{{ route('checkin') }}" variant="primary" class="bg-purple-600 hover:bg-purple-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Check-In Sekarang</span>
                </x-buttons.link-button>

                <x-buttons.link-button href="{{ route('home') }}" variant="secondary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Kembali ke Beranda</span>
                </x-buttons.link-button>
            </div>
        </div>
    </div>
</x-layout.layout>
