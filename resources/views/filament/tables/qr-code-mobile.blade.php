@php
    $record = $getRecord();
    $programLabels = ['carwash' => 'Cuci Mobil', 'motorwash' => 'Cuci Motor', 'coffeeshop' => 'Coffee Shop'];
@endphp

<div class="px-3 py-3 text-start">
    <div class="flex items-center gap-2">
        <span @class([
            'h-2 w-2 shrink-0 rounded-full',
            'bg-success-500' => $record->is_active,
            'bg-gray-400' => ! $record->is_active,
        ]) title="{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}"></span>
        <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $record->name ?: $record->code }}</p>
    </div>
    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
        {{ collect([$record->location, $record->scan_count . 'x scan'])->filter()->join(' · ') }}
    </p>

    <div class="mt-2 flex flex-wrap gap-1.5">
        <x-filament::badge size="sm" :color="$record->qr_type === 'permanent' ? 'success' : 'warning'">
            {{ $record->qr_type === 'permanent' ? 'Permanent' : 'One-time' }}
        </x-filament::badge>
        @foreach((array) $record->loyalty_types as $type)
            <x-filament::badge size="sm" color="gray">{{ $programLabels[$type] ?? $type }}</x-filament::badge>
        @endforeach
    </div>
</div>
