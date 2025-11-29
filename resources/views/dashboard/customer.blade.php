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

        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="text-center mb-6">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full {{ $hasReward ? 'bg-gradient-to-br from-green-400 to-emerald-500' : 'bg-gradient-to-br from-yellow-400 to-orange-400' }} flex items-center justify-center shadow-lg">
                    @if($hasReward)
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                        </svg>
                    @else
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    @endif
                </div>
                <h3 class="text-lg text-gray-600 mb-2">Poin Kamu Saat Ini</h3>
                <div class="text-6xl font-bold {{ $hasReward ? 'text-green-600' : 'text-gray-800' }}">
                    {{ $customer->current_points }}/5
                </div>
            </div>

            <div class="mb-6">
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div 
                        class="bg-gradient-to-r from-purple-500 to-pink-500 h-4 rounded-full transition-all"
                        style="width: {{ ($customer->current_points / 5) * 100 }}%"
                    ></div>
                </div>
                <p class="text-center mt-2 text-sm text-gray-600">
                    @if($hasReward)
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Kamu berhak dapat diskon! Scan QR untuk klaim
                        </span>
                    @else
                        {{ $pointsToReward . ' poin lagi untuk diskon!' }}
                    @endif
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
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
</x-layout.layout>
