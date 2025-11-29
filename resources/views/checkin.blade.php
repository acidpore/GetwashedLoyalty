<x-layout.layout title="Check-In - Getwashed Loyalty" bg-class="bg-gradient-to-br from-green-50 to-emerald-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="mb-4">
                <x-buttons.back-button href="{{ route('home') }}" text="Kembali" />
            </div>

            <x-cards.white-card>
                <x-ui.page-header title="Check-In Sekarang" description="Isi data di bawah untuk dapatkan poin">
                    <x-slot:icon>
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </x-slot:icon>
                </x-ui.page-header>

                @if(session('success'))
                    <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
                @endif

                @if(session('error'))
                    <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
                @endif

                <form method="POST" action="{{ route('checkin.store') }}" class="space-y-6">
                    @csrf

                    <x-forms.form-input 
                        label="Nama Lengkap" 
                        name="name" 
                        placeholder="Masukkan nama Anda"
                        required
                    />

                    <x-forms.form-input 
                        label="Nomor WhatsApp" 
                        name="phone" 
                        type="tel"
                        placeholder="08123456789"
                        required
                    >
                        Format: 08xxx atau 628xxx
                    </x-forms.form-input>

                    <x-buttons.action-button type="submit" variant="success">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        <span>Dapatkan Poin Sekarang!</span>
                    </x-buttons.action-button>
                </form>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-blue-800">
                            Poin akan langsung masuk dan notifikasi dikirim ke WhatsApp Anda
                        </p>
                    </div>
                </div>
            </x-cards.white-card>

            <p class="text-center text-xs text-gray-600 mt-4">
                Data Anda aman dan hanya digunakan untuk program loyalitas
            </p>
        </div>
    </div>
</x-layout.layout>
