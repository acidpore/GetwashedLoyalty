<x-layout title="Dashboard - Getwashed Loyalty" bg-class="bg-gray-50">
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
            <h2 class="text-3xl font-bold mb-2">Halo, {{ $user->name }}! 👋</h2>
            <p class="text-purple-100">Selamat datang di dashboard loyalitas Anda</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <div class="text-center mb-6">
                <div class="text-6xl mb-4">{{ $hasReward ? '🎁' : '⭐' }}</div>
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
                    {{ $hasReward ? '🎉 Kamu berhak dapat diskon! Scan QR untuk klaim' : $pointsToReward . ' poin lagi untuk diskon!' }}
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
                <span>📜</span> Riwayat Kunjungan
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
                    <div class="text-4xl mb-2">📭</div>
                    <p>Belum ada riwayat kunjungan</p>
                </div>
            @endforelse
        </div>

        <div class="space-y-3">
            <a href="{{ route('checkin') }}" class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl text-center transition">
                🚗 Check-In Sekarang
            </a>
            <a href="{{ route('home') }}" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 rounded-xl text-center transition">
                🏠 Kembali ke Beranda
            </a>
        </div>
    </div>
</x-layout>
