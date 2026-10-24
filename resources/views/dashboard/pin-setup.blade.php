<x-layout.layout :title="($hasPin ? 'Ganti PIN' : 'Atur PIN') . ' - Getwashed x Latte'">
    <div class="min-h-screen flex flex-col">
        <header class="max-w-sm w-full mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center gap-1.5 -ml-2 px-2 py-1.5 rounded-lg text-sm font-semibold text-ink-2 hover:text-ink transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                Dashboard
            </a>
            <x-theme-toggle />
        </header>

        <main class="flex-1 max-w-sm w-full mx-auto px-4 pb-10">
            <div class="pt-4 pb-8">
                <div class="size-14 rounded-2xl bg-subtle text-accent inline-flex items-center justify-center">
                    <span class="material-symbols-outlined icon-fill !text-[28px]">lock</span>
                </div>
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight">{{ $hasPin ? 'Ganti PIN' : 'Atur PIN' }}</h1>
                <p class="mt-1.5 text-ink-2">PIN dipakai bersama nomor WhatsApp untuk masuk.</p>
            </div>

            <div class="space-y-3 mb-5">
                @if(session('info'))
                    <x-alert>{{ session('info') }}</x-alert>
                @endif
                @if(session('error'))
                    <x-alert type="error">{{ session('error') }}</x-alert>
                @endif
                @if($errors->any())
                    <x-alert type="error">{{ $errors->first() }}</x-alert>
                @endif
            </div>

            <form method="POST" action="{{ route('customer.pin.store') }}" class="rounded-3xl bg-surface border border-line p-5 space-y-5">
                @csrf
                <div>
                    <label for="pin" class="block text-sm font-semibold mb-1.5">PIN baru</label>
                    <input id="pin" type="password" name="pin" required maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="new-password"
                           placeholder="6 digit angka"
                           class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink text-lg tracking-[0.4em] font-bold placeholder:text-base placeholder:tracking-normal placeholder:font-normal placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <label for="pin_confirmation" class="block text-sm font-semibold mb-1.5">Ulangi PIN</label>
                    <input id="pin_confirmation" type="password" name="pin_confirmation" required maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="new-password"
                           placeholder="6 digit angka"
                           class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink text-lg tracking-[0.4em] font-bold placeholder:text-base placeholder:tracking-normal placeholder:font-normal placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                </div>
                <button type="submit"
                        class="w-full rounded-xl bg-accent-solid px-5 py-3.5 font-bold text-white hover:brightness-110 active:scale-[0.99] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent focus-visible:ring-offset-surface">
                    {{ $hasPin ? 'Simpan PIN baru' : 'Atur PIN' }}
                </button>
            </form>

            <a href="{{ route('customer.dashboard') }}" class="mt-4 block text-center text-sm font-semibold text-ink-3 hover:text-ink transition-colors py-2">
                {{ $hasPin ? 'Batal' : 'Lewati dulu' }}
            </a>

            <p class="mt-4 flex items-start gap-2 text-sm text-ink-3">
                <span class="material-symbols-outlined shrink-0">info</span>
                Lupa PIN? Check-in ulang di outlet, link untuk atur PIN baru dikirim ke WhatsApp.
            </p>
        </main>
    </div>
</x-layout.layout>
