@php
    $record = $getRecord();
    $points = $record->getPoints($type);
    $visits = (int) $record->{"{$type}_total_visits"};
    $lastVisit = $record->{"{$type}_last_visit_at"};
@endphp

<div class="px-3 py-3 text-start">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $record->user?->name ?? '-' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->user?->phone ?? '-' }}</p>
        </div>
        <x-filament::badge :color="$record->hasReward($type) ? 'success' : 'gray'">
            {{ $points }} poin
        </x-filament::badge>
    </div>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ $visits }}x kunjungan{{ $lastVisit ? ', terakhir ' . $lastVisit->format('d/m/y') : '' }}
    </p>
</div>
