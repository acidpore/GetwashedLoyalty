@php
    $record = $getRecord();
    $programs = ['carwash' => 'CW', 'coffeeshop' => 'CS', 'motorwash' => 'MW'];
@endphp

<div class="px-3 py-3 text-start">
    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $record->user?->name ?? '-' }}</p>
    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->user?->phone ?? '-' }}</p>

    <div class="mt-2 flex flex-wrap gap-1.5">
        @foreach($programs as $type => $short)
            <x-filament::badge
                size="sm"
                :color="$record->getPoints($type) >= \App\Models\SystemSetting::firstMilestonePoints($type) ? 'success' : 'gray'"
                :tooltip="\Illuminate\Support\Str::headline($type)"
            >
                {{ $short }} {{ $record->getPoints($type) }}
            </x-filament::badge>
        @endforeach
    </div>
</div>
