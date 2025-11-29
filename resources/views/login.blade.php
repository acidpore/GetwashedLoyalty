<x-layout.layout title="Login - Getwashed Loyalty">
    @push('scripts')
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endpush

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md" x-data="{ tab: 'customer', phone: '' }">
            <div class="mb-4">
                <x-buttons.back-button href="{{ route('home') }}" text="Kembali" />
            </div>

            <x-cards.white-card>
                <x-ui.page-header title="Login" description="Pilih metode login Anda">
                    <x-slot:icon>
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </x-slot:icon>
                </x-ui.page-header>

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
                    <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
                @endif

                @if(session('error'))
                    <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
                @endif

                <div x-show="tab === 'customer'" x-cloak>
                    <form method="POST" action="{{ route('login.otp.request') }}" class="space-y-6">
                        @csrf
                        <x-forms.form-input 
                            label="Nomor WhatsApp" 
                            name="phone" 
                            type="tel" 
                            placeholder="08123456789"
                            required
                            x-model="phone"
                        >
                            Nomor yang terdaftar saat check-in
                        </x-forms.form-input>

                        <x-buttons.action-button type="submit" variant="primary">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="5" y="2" width="14" height="20" rx="2" stroke-width="2"/>
                                <path d="M12 18h.01" stroke-linecap="round" stroke-width="2"/>
                            </svg>
                            <span>Kirim Kode OTP</span>
                        </x-buttons.action-button>
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
                            <x-buttons.action-button type="submit" variant="green">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Verifikasi & Login</span>
                            </x-buttons.action-button>
                        </form>
                    </div>
                </div>

                <div x-show="tab === 'admin'" x-cloak>
                    <form method="POST" action="{{ route('login.admin') }}" class="space-y-6">
                        @csrf
                        <x-forms.form-input 
                            label="Email" 
                            name="email" 
                            type="email" 
                            placeholder="admin@getwashed.com"
                            required
                        />

                        <x-forms.form-input 
                            label="Password" 
                            name="password" 
                            type="password" 
                            placeholder="••••••••"
                            required
                        />

                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="remember" 
                                name="remember" 
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            >
                            <label for="remember" class="ml-2 text-sm text-gray-700">Remember me</label>
                        </div>

                        <x-buttons.action-button type="submit" variant="primary" class="bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            <span>Login Admin</span>
                        </x-buttons.action-button>
                    </form>
                </div>
            </x-cards.white-card>

            <p class="text-center text-xs text-gray-600 mt-4">
                Belum punya akun? Scan QR di kasir untuk check-in pertama
            </p>
        </div>
    </div>
</x-layout.layout>
