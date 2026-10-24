<x-layout.layout title="Dashboard - Getwashed Loyalty">
    @php
        $programImages = [
            'carwash' => 'carwash.png',
            'motorwash' => 'motorcycle.png',
            'coffeeshop' => 'drink.png',
        ];
        $programLabels = [
            'carwash' => 'Car Wash',
            'motorwash' => 'Motor Wash',
            'coffeeshop' => 'Coffee Shop',
        ];
        $pendingRewards = collect($loyaltyPrograms)->where('has_reward', true);
        $totalVisits = $customer->carwash_total_visits + $customer->motorwash_total_visits + $customer->coffeeshop_total_visits;
        $lastVisit = collect([
            $customer->carwash_last_visit_at,
            $customer->motorwash_last_visit_at,
            $customer->coffeeshop_last_visit_at,
        ])->filter()->max();
    @endphp

    <div class="min-h-screen pb-28">
        <header class="sticky top-0 z-40 bg-canvas/85 backdrop-blur-md border-b border-line/60">
            <div class="max-w-md mx-auto px-4 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    <img src="{{ asset('Logo Get Washed Updated.png') }}" alt="" class="size-8 rounded-lg object-contain">
                    <span class="font-bold tracking-tight">Getwashed</span>
                </a>
                <x-theme-toggle />
            </div>
        </header>

        <main class="max-w-md mx-auto px-4 pt-6 space-y-6">
            <section>
                <p class="text-sm text-ink-3">Halo,</p>
                <h1 class="text-2xl font-extrabold tracking-tight truncate">{{ $user->name }}</h1>
            </section>

            @if(session('success'))
                <x-alert type="success">{{ session('success') }}</x-alert>
            @endif

            @if($pendingRewards->isNotEmpty())
                <section class="rounded-3xl bg-positive-soft p-5">
                    <div class="flex items-center gap-2 text-positive">
                        <span class="material-symbols-outlined icon-fill">redeem</span>
                        <h2 class="font-bold">Hadiah siap diklaim</h2>
                    </div>
                    <ul class="mt-3 space-y-2">
                        @foreach($pendingRewards as $program)
                            <li class="flex items-center justify-between gap-3 rounded-2xl bg-surface px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-ink-3">{{ $program['name'] }}</p>
                                    <p class="font-bold truncate">{{ $program['earned_reward'] }}</p>
                                </div>
                                <span class="material-symbols-outlined text-positive shrink-0">check_circle</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-3 text-sm text-ink-2">
                        Tunjukkan halaman ini ke kasir. Poin terkunci sampai hadiah diklaim, setelah itu bisa lanjut dikumpulkan.
                    </p>
                </section>
            @endif

            <section class="space-y-3" aria-label="Program loyalty">
                @foreach($loyaltyPrograms as $program)
                    @php
                        $type = $program['type'];
                        $max = max(1, $program['max_points']);
                        $progress = min(100, $program['points'] / $max * 100);
                    @endphp
                    <article class="rounded-3xl bg-surface border border-line p-5">
                        <div class="flex items-center gap-4">
                            <div class="size-12 shrink-0 rounded-2xl bg-{{ $type }} p-2.5">
                                <img src="{{ asset($programImages[$type]) }}" alt="" class="size-full object-contain">
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="font-bold">{{ $program['name'] }}</h2>
                                <p class="text-sm text-ink-3">
                                    @if($program['has_reward'])
                                        <span class="font-semibold text-positive">Siap ditukar</span>
                                    @elseif($program['next_reward'])
                                        {{ $program['points_until_reward'] }} poin lagi
                                    @else
                                        Semua hadiah tercapai
                                    @endif
                                </p>
                            </div>
                            <p class="text-right shrink-0">
                                <span class="text-2xl font-extrabold tabular-nums">{{ $program['points'] }}</span>
                                <span class="text-sm font-semibold text-ink-3 tabular-nums">/{{ $program['max_points'] }}</span>
                            </p>
                        </div>

                        <div class="relative mt-5 h-2 rounded-full bg-subtle" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $program['max_points'] }}" aria-valuenow="{{ $program['points'] }}" aria-label="Poin {{ $program['name'] }}">
                            <div class="h-full rounded-full bg-{{ $type }} transition-all duration-700" style="width: {{ $progress }}%"></div>
                            @foreach($program['milestones'] as $milestone)
                                @if($milestone['at'] < $max)
                                    <span class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 size-1.5 rounded-full bg-surface" style="left: {{ $milestone['at'] / $max * 100 }}%"></span>
                                @endif
                            @endforeach
                        </div>

                        <ol class="mt-4 space-y-2">
                            @foreach($program['milestones'] as $milestone)
                                @php
                                    $isClaimed = $milestone['at'] <= $program['claimed'];
                                    $isReady = ! $isClaimed && $program['points'] >= $milestone['at'];
                                @endphp
                                <li class="flex items-center gap-3 text-sm">
                                    <span @class([
                                        'size-7 shrink-0 rounded-full inline-flex items-center justify-center text-xs font-bold tabular-nums',
                                        'bg-subtle text-ink-3' => ! $isClaimed && ! $isReady,
                                        'bg-positive-soft text-positive' => $isReady,
                                        "bg-{$type} text-white" => $isClaimed,
                                    ])>
                                        @if($isClaimed)
                                            <span class="material-symbols-outlined text-base">check</span>
                                        @else
                                            {{ $milestone['at'] }}
                                        @endif
                                    </span>
                                    <span @class(['flex-1 min-w-0 truncate', 'text-ink-3' => ! $isReady && ! $isClaimed, 'font-semibold' => $isReady])>
                                        {{ $milestone['reward'] }}
                                    </span>
                                    @if($isClaimed)
                                        <span class="text-xs font-semibold text-ink-3 shrink-0">Diklaim</span>
                                    @elseif($isReady)
                                        <span class="text-xs font-bold text-positive shrink-0">Siap</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @endforeach
            </section>

            <section class="grid grid-cols-2 gap-3">
                <div class="rounded-3xl bg-surface border border-line p-4">
                    <p class="text-xs font-semibold text-ink-3">Total kunjungan</p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums">{{ $totalVisits }}</p>
                </div>
                <div class="rounded-3xl bg-surface border border-line p-4">
                    <p class="text-xs font-semibold text-ink-3">Kunjungan terakhir</p>
                    <p class="mt-1 font-bold">{{ $lastVisit ? $lastVisit->diffForHumans() : '-' }}</p>
                </div>
            </section>

            <section>
                <h2 class="font-bold mb-3">Riwayat</h2>
                @if($recentVisits->isEmpty())
                    <div class="rounded-3xl border border-dashed border-line p-8 text-center">
                        <span class="material-symbols-outlined text-ink-3 text-3xl">history</span>
                        <p class="mt-2 text-sm text-ink-3">Belum ada kunjungan</p>
                    </div>
                @else
                    <ul class="rounded-3xl bg-surface border border-line divide-y divide-line overflow-hidden">
                        @foreach($recentVisits as $visit)
                            <li class="flex items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold truncate">
                                        {{ collect($visit->loyalty_types ?? [])->map(fn ($type) => $programLabels[$type] ?? $type)->join(', ') ?: 'Kunjungan' }}
                                    </p>
                                    <p class="text-xs text-ink-3">{{ $visit->visited_at->translatedFormat('d M Y, H:i') }} WIB</p>
                                </div>
                                <span @class([
                                    'shrink-0 rounded-full px-2.5 py-1 text-xs font-bold tabular-nums',
                                    'bg-positive-soft text-positive' => $visit->points_earned > 0,
                                    'bg-subtle text-ink-3' => $visit->points_earned <= 0,
                                ])>+{{ $visit->points_earned }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </main>

        <nav class="fixed bottom-0 inset-x-0 z-40 bg-surface/90 backdrop-blur-md border-t border-line" aria-label="Navigasi">
            <div class="max-w-md mx-auto grid grid-cols-3 px-4 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))]">
                <a href="{{ route('customer.dashboard') }}" aria-current="page" class="flex flex-col items-center gap-0.5 py-1.5 rounded-xl text-accent">
                    <span class="material-symbols-outlined icon-fill">loyalty</span>
                    <span class="text-xs font-semibold">Poin</span>
                </a>
                <a href="{{ route('customer.pin.setup') }}" class="flex flex-col items-center gap-0.5 py-1.5 rounded-xl text-ink-3 hover:text-ink transition-colors">
                    <span class="material-symbols-outlined">password</span>
                    <span class="text-xs font-semibold">PIN</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="contents">
                    @csrf
                    <button type="submit" class="flex flex-col items-center gap-0.5 py-1.5 rounded-xl text-ink-3 hover:text-ink transition-colors">
                        <span class="material-symbols-outlined">logout</span>
                        <span class="text-xs font-semibold">Keluar</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</x-layout.layout>
