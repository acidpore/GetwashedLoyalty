<x-layout.layout title="Check-in Berhasil - Getwashed Loyalty">
    <div class="min-h-screen flex flex-col">
        <header class="max-w-md w-full mx-auto px-4 h-16 flex items-center justify-end">
            <x-theme-toggle />
        </header>

        <main class="flex-1 max-w-md w-full mx-auto px-4 pb-10 space-y-5">
            <section class="pt-2 text-center">
                <div @class([
                    'mx-auto size-16 rounded-full inline-flex items-center justify-center',
                    'bg-positive-soft text-positive' => $hasReward,
                    'bg-subtle text-accent' => ! $hasReward,
                ])>
                    <span class="material-symbols-outlined icon-fill !text-[32px]">{{ $hasReward ? 'redeem' : 'check_circle' }}</span>
                </div>
                <h1 class="mt-4 text-2xl font-extrabold tracking-tight">
                    {{ $hasReward ? 'Selamat, kamu dapat hadiah!' : 'Check-in berhasil' }}
                </h1>
                <p class="mt-1 text-ink-2">Terima kasih, <span class="font-semibold text-ink">{{ $name }}</span></p>
            </section>

            @if($hasReward)
                <section class="rounded-3xl bg-positive-soft p-5 space-y-2">
                    @foreach($programs->where('has_reward', true) as $program)
                        <div class="flex items-center gap-3 rounded-2xl bg-surface px-4 py-3">
                            <span class="size-9 shrink-0 rounded-xl bg-{{ $program['type'] }} p-1.5">
                                <img src="{{ asset($program['image']) }}" alt="" class="size-full object-contain">
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-ink-3">{{ $program['name'] }}</p>
                                <p class="font-bold truncate">{{ $program['earned_reward'] }}</p>
                            </div>
                        </div>
                    @endforeach
                    <p class="pt-1 text-sm text-ink-2">
                        Tunjukkan pesan WhatsApp ke kasir. Poin terkunci sampai hadiah diklaim, setelah itu bisa lanjut dikumpulkan.
                    </p>
                </section>
            @endif

            <section class="space-y-3">
                @foreach($programs as $program)
                    <article class="rounded-3xl bg-surface border border-line p-5">
                        <div class="flex items-center gap-4">
                            <span class="size-11 shrink-0 rounded-2xl bg-{{ $program['type'] }} p-2">
                                <img src="{{ asset($program['image']) }}" alt="" class="size-full object-contain">
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="font-bold">{{ $program['name'] }}</h2>
                                <p class="text-sm text-ink-3 truncate">
                                    @if($program['has_reward'])
                                        <span class="font-semibold text-positive">Siap ditukar</span>
                                    @elseif($program['next_reward'])
                                        {{ $program['points_until_reward'] }} poin lagi: {{ $program['next_reward'] }}
                                    @else
                                        Semua hadiah tercapai
                                    @endif
                                </p>
                            </div>
                            <p class="shrink-0 text-right">
                                <span class="text-2xl font-extrabold tabular-nums">{{ $program['points'] }}</span>
                                <span class="text-sm font-semibold text-ink-3 tabular-nums">/{{ $program['max_points'] }}</span>
                            </p>
                        </div>
                        <div class="mt-4 h-2 rounded-full bg-subtle">
                            <div class="h-full rounded-full bg-{{ $program['type'] }} transition-all duration-700"
                                 style="width: {{ min(100, $program['points'] / max(1, $program['max_points']) * 100) }}%"></div>
                        </div>
                    </article>
                @endforeach
            </section>

            <a href="{{ route('home') }}"
               class="block w-full rounded-xl bg-accent-solid px-5 py-3.5 text-center font-bold text-white hover:brightness-110 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent focus-visible:ring-offset-canvas">
                Selesai
            </a>
        </main>
    </div>
</x-layout.layout>
