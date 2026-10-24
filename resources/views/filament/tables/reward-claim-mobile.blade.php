@php
    $record = $getRecord();
@endphp

<div class="px-3 py-3 text-start">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $record->customer?->user?->name ?? '-' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->customer?->user?->phone ?? '-' }}</p>
        </div>
        <x-filament::badge size="sm" :color="match ($record->loyalty_type) { 'carwash' => 'info', 'motorwash' => 'warning', default => 'success' }">
            {{ \App\Filament\Resources\RewardClaimResource::PROGRAMS[$record->loyalty_type] ?? $record->loyalty_type }}
        </x-filament::badge>
    </div>
    <p class="mt-1.5 text-sm text-gray-950 dark:text-white">{{ $record->reward }} <span class="text-xs text-gray-500 dark:text-gray-400">({{ $record->points_used }} poin)</span></p>
    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
        {{ $record->claimed_at?->format('d/m/y H:i') }}{{ $record->claimedBy ? ', oleh ' . $record->claimedBy->name : '' }}
    </p>
</div>
