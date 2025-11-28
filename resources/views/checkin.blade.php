<x-layout title="Check-In - Getwashed Loyalty" bg-class="bg-gradient-to-br from-green-50 to-emerald-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="inline-block mb-4 text-gray-600 hover:text-gray-800">← Kembali</a>

            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">🚗</div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Check-In Sekarang</h1>
                    <p class="text-gray-600">Isi data di bawah untuk dapatkan poin</p>
                </div>

                @if(session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('checkin.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition"
                            placeholder="Masukkan nama Anda"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Nomor WhatsApp</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="{{ old('phone') }}"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring focus:ring-emerald-200 transition"
                            placeholder="08123456789"
                        >
                        <p class="mt-1 text-xs text-gray-500">Format: 08xxx atau 628xxx</p>
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-xl transition transform hover:scale-105"
                    >
                        ✨ Dapatkan Poin Sekarang!
                    </button>
                </form>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-800 text-center">
                        💡 Poin akan langsung masuk dan notifikasi dikirim ke WhatsApp Anda
                    </p>
                </div>
            </div>

            <p class="text-center text-xs text-gray-600 mt-4">
                Data Anda aman dan hanya digunakan untuk program loyalitas
            </p>
        </div>
    </div>
</x-layout>
