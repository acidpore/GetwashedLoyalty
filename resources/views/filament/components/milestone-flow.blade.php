{{-- Classes stay within what Filament's compiled CSS already ships --}}
<div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    @if($steps->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada milestone.</p>
    @else
        <ol class="relative space-y-4">
            @foreach($steps as $step)
                <li class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold text-gray-950 ring-1 ring-gray-950/10 dark:bg-gray-900 dark:text-white dark:ring-white/20">
                        {{ $step['at'] }}
                    </span>
                    <div class="min-w-0 pt-1">
                        <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $step['reward'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $loop->last ? 'Klaim, poin kembali ke 0' : 'Poin berhenti sampai diklaim' }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="mt-4 border-t border-gray-200 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
            Customer tidak bisa melewati milestone. Kasir klaim hadiah dulu, baru poin bisa lanjut ke milestone berikutnya.
        </p>
    @endif
</div>
