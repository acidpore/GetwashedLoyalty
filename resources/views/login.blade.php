<x-layout title="Login - Getwashed Loyalty">
    @push('scripts')
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md" x-data="{ tab: 'customer' }">
            <a href="{{ route('home') }}" class="inline-block mb-4 text-gray-600 hover:text-gray-800">← Kembali</a>

            <div class="bg-white rounded-3xl shadow-2xl p-8">
                <div class="text-center mb-8">
                    <div class="text-6xl mb-4">🔐</div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Login</h1>
                    <p class="text-gray-600">Pilih metode login Anda</p>
                </div>

                <div class="flex mb-8 bg-gray-100 rounded-xl p-1">
                    <button 
                        @click="tab = 'customer'"
                        :class="tab === 'customer' ? 'bg-white shadow-md' : ''"
                        class="flex-1 py-2 px-4 rounded-lg font-semibold transition"
                    >
                        Customer (OTP)
                    </button>
                    <button 
                        @click="tab = 'admin'"
                        :class="tab === 'admin' ? 'bg-white shadow-md' : ''"
                        class="flex-1 py-2 px-4 rounded-lg font-semibold transition"
                    >
                        Admin
                    </button>
                </div>

                @if(session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <div x-show="tab === 'customer'" x-cloak>
                    <form method="POST" action="{{ route('login.otp.request') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label for="customer_phone" class="block text-sm font-semibold text-gray-700 mb-2">Nomor WhatsApp</label>
                            <input 
                                type="tel" 
                                id="customer_phone" 
                                name="phone" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition"
                                placeholder="08123456789"
                            >
                            <p class="mt-1 text-xs text-gray-500">Nomor yang terdaftar saat check-in</p>
                        </div>

                        <button 
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition"
                        >
                            📱 Kirim Kode OTP
                        </button>
                    </form>

                    <div class="mt-8 p-4 bg-gray-50 rounded-xl">
                        <p class="text-sm font-semibold text-gray-700 mb-4">Sudah terima OTP?</p>
                        <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="phone" x-model="phone">
                            <input 
                                type="text" 
                                name="otp_code" 
                                maxlength="6"
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring focus:ring-blue-200 transition text-center text-2xl tracking-widest"
                                placeholder="000000"
                            >
                            <button 
                                type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition"
                            >
                                ✅ Verifikasi & Login
                            </button>
                        </form>
                    </div>
                </div>

                <div x-show="tab === 'admin'" x-cloak>
                    <form method="POST" action="{{ route('login.admin') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label for="admin_email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input 
                                type="email" 
                                id="admin_email" 
                                name="email" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                                placeholder="admin@getwashed.com"
                            >
                        </div>

                        <div>
                            <label for="admin_password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                            <input 
                                type="password" 
                                id="admin_password" 
                                name="password" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                                placeholder="••••••••"
                            >
                        </div>

                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember" 
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            >
                            <label for="remember" class="ml-2 text-sm text-gray-700">Remember me</label>
                        </div>

                        <button 
                            type="submit"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition"
                        >
                            🔑 Login Admin
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-xs text-gray-600 mt-4">
                Belum punya akun? Scan QR di kasir untuk check-in pertama
            </p>
        </div>
    </div>
</x-layout>
