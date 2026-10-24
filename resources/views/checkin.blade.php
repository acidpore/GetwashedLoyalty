<x-layout.layout title="Check-In - Getwashed Loyalty">
    @php
        $programs = [
            'carwash' => ['label' => 'Car Wash', 'image' => 'carwash.png'],
            'motorwash' => ['label' => 'Motor Wash', 'image' => 'motorcycle.png'],
            'coffeeshop' => ['label' => 'Coffee Shop', 'image' => 'drink.png'],
        ];
    @endphp

    <div class="min-h-screen flex flex-col">
        <header class="max-w-md w-full mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 -ml-2 px-2 py-1.5 rounded-lg text-sm font-semibold text-ink-2 hover:text-ink transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                Kembali
            </a>
            <x-theme-toggle />
        </header>

        <main class="flex-1 max-w-md w-full mx-auto px-4 pb-10">
            <div class="pt-4 pb-8">
                <img src="{{ asset('Logo Get Washed Updated.png') }}" alt="Getwashed" class="size-14 rounded-2xl object-contain">
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight">Check-in</h1>
                <p class="mt-1.5 text-ink-2">Isi nama dan nomor WhatsApp untuk dapat poin.</p>

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach($loyaltyTypes as $type)
                        @isset($programs[$type])
                            <span class="inline-flex items-center gap-2 rounded-full bg-surface border border-line pl-1 pr-3 py-1 text-sm font-semibold">
                                <span class="size-6 rounded-full bg-{{ $type }} p-1">
                                    <img src="{{ asset($programs[$type]['image']) }}" alt="" class="size-full object-contain">
                                </span>
                                {{ $programs[$type]['label'] }}
                            </span>
                        @endisset
                    @endforeach
                    @if(count($loyaltyTypes) > 1)
                        <span class="inline-flex items-center rounded-full bg-caution-soft text-caution px-3 py-1 text-sm font-bold">
                            {{ count($loyaltyTypes) }}x poin
                        </span>
                    @endif
                </div>
            </div>

            <div class="space-y-3 mb-5">
                @if(session('success'))
                    <x-alert type="success">{{ session('success') }}</x-alert>
                @endif
                @if(session('error'))
                    <x-alert type="error">{{ session('error') }}</x-alert>
                @endif
                @if($errors->any())
                    <x-alert type="error">{{ $errors->first() }}</x-alert>
                @endif
            </div>

            <form method="POST" action="{{ route('checkin.store') }}" class="rounded-3xl bg-surface border border-line p-5 space-y-5"
                  x-data="{ sending: false }" @submit="sending = true">
                @csrf
                @foreach($loyaltyTypes as $type)
                    <input type="hidden" name="loyalty_types[]" value="{{ $type }}">
                @endforeach
                @if($qrCode)
                    <input type="hidden" name="qr_code" value="{{ $qrCode->code }}">
                @endif

                <div>
                    <label for="full-name" class="block text-sm font-semibold mb-1.5">Nama lengkap</label>
                    <input id="full-name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}"
                           placeholder="Nama kamu"
                           class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                </div>

                <div>
                    <label for="whatsapp-number" class="block text-sm font-semibold mb-1.5">Nomor WhatsApp</label>
                    <input id="whatsapp-number" name="phone" type="tel" required inputmode="tel" autocomplete="tel" value="{{ old('phone') }}"
                           placeholder="08123456789" aria-describedby="phone-hint"
                           class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                    <p id="phone-hint" class="mt-1.5 text-xs text-ink-3">Format 08xxx atau 628xxx</p>
                </div>

                <button type="submit" :disabled="sending"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-accent-solid px-5 py-3.5 font-bold text-white hover:brightness-110 active:scale-[0.99] disabled:opacity-60 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent focus-visible:ring-offset-surface">
                    <span x-show="!sending">Dapatkan poin</span>
                    <span x-show="sending" x-cloak>Memproses...</span>
                </button>
            </form>

            <p class="mt-5 flex items-start gap-2 text-sm text-ink-3">
                <span class="material-symbols-outlined shrink-0">info</span>
                Poin langsung masuk. Notifikasi dan link dashboard dikirim ke WhatsApp kamu.
            </p>
        </main>
    </div>
</x-layout.layout>
