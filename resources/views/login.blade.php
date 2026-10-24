<x-layout.layout title="Masuk - Getwashed x Latte">
    <div class="min-h-screen flex flex-col" x-data="{ tab: '{{ old('email') ? 'admin' : 'customer' }}', showPinHelp: false }">
        <header class="max-w-sm w-full mx-auto px-4 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 -ml-2 px-2 py-1.5 rounded-lg text-sm font-semibold text-ink-2 hover:text-ink transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
                Beranda
            </a>
            <x-theme-toggle />
        </header>

        <main class="flex-1 max-w-sm w-full mx-auto px-4 pb-10">
            <div class="pt-4 pb-8">
                <img src="{{ asset('Logo Get Washed Updated.png') }}" alt="Getwashed" class="size-14 rounded-2xl object-contain">
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight">Masuk</h1>
                <p class="mt-1.5 text-ink-2">Cek poin dan hadiah kamu.</p>
            </div>

            <div class="grid grid-cols-2 gap-1 rounded-xl bg-subtle p-1 mb-5" role="tablist">
                <button type="button" role="tab" @click="tab = 'customer'" :aria-selected="tab === 'customer'"
                        :class="tab === 'customer' ? 'bg-surface text-ink shadow-sm' : 'text-ink-3 hover:text-ink'"
                        class="rounded-lg py-2.5 text-sm font-bold transition">Customer</button>
                <button type="button" role="tab" @click="tab = 'admin'" :aria-selected="tab === 'admin'"
                        :class="tab === 'admin' ? 'bg-surface text-ink shadow-sm' : 'text-ink-3 hover:text-ink'"
                        class="rounded-lg py-2.5 text-sm font-bold transition">Admin</button>
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

            <div x-show="tab === 'customer'">
                <form method="POST" action="{{ route('login.pin') }}" class="rounded-3xl bg-surface border border-line p-5 space-y-5">
                    @csrf
                    <div>
                        <label for="phone" class="block text-sm font-semibold mb-1.5">Nomor WhatsApp</label>
                        <input id="phone" type="tel" name="phone" required inputmode="tel" autocomplete="tel" value="{{ old('phone') }}"
                               placeholder="08123456789"
                               class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                    </div>
                    <div>
                        <label for="pin" class="block text-sm font-semibold mb-1.5">PIN</label>
                        <input id="pin" type="password" name="pin" required maxlength="6" inputmode="numeric" pattern="[0-9]*" autocomplete="current-password"
                               placeholder="6 digit"
                               class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink text-lg tracking-[0.4em] font-bold placeholder:text-base placeholder:tracking-normal placeholder:font-normal placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                    </div>
                    <button type="submit"
                            class="w-full rounded-xl bg-accent-solid px-5 py-3.5 font-bold text-white hover:brightness-110 active:scale-[0.99] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent focus-visible:ring-offset-surface">
                        Masuk
                    </button>
                </form>

                <div class="mt-5 text-sm">
                    <button type="button" @click="showPinHelp = !showPinHelp" :aria-expanded="showPinHelp"
                            class="inline-flex items-center gap-1 font-semibold text-accent hover:underline underline-offset-4">
                        Lupa atau belum punya PIN?
                        <span class="material-symbols-outlined transition-transform" :class="showPinHelp && 'rotate-180'">expand_more</span>
                    </button>
                    <p x-show="showPinHelp" x-cloak x-transition.opacity class="mt-2 text-ink-2 leading-relaxed">
                        Scan QR Code di outlet untuk check-in. Kami kirim link ke WhatsApp kamu untuk masuk otomatis dan mengatur PIN baru.
                    </p>
                </div>
            </div>

            <div x-show="tab === 'admin'" x-cloak>
                <form method="POST" action="{{ route('login.admin') }}" class="rounded-3xl bg-surface border border-line p-5 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-semibold mb-1.5">Email</label>
                        <input id="email" type="email" name="email" required autocomplete="username" value="{{ old('email') }}"
                               placeholder="admin@example.com"
                               class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink placeholder:text-ink-3 focus:border-accent focus:ring-accent">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-semibold">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-accent hover:underline underline-offset-4">Lupa?</a>
                            @endif
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="w-full rounded-xl border-line bg-canvas px-4 py-3 text-ink focus:border-accent focus:ring-accent">
                    </div>
                    <label class="flex items-center gap-2.5 cursor-pointer text-sm text-ink-2">
                        <input type="checkbox" name="remember" class="size-4 rounded border-line bg-canvas text-accent-solid focus:ring-accent">
                        Ingat saya
                    </label>
                    <button type="submit"
                            class="w-full rounded-xl bg-accent-solid px-5 py-3.5 font-bold text-white hover:brightness-110 active:scale-[0.99] transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent focus-visible:ring-offset-surface">
                        Masuk sebagai admin
                    </button>
                </form>
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-ink-3">&copy; {{ date('Y') }} Getwashed x Latte</footer>
    </div>
</x-layout.layout>
