@props(['type' => 'success'])

@php
    $classes = match($type) {
        'success' => 'bg-green-100 border-green-400 text-green-700',
        'error' => 'bg-red-100 border-red-400 text-red-700',
        'info' => 'bg-blue-100 border-blue-400 text-blue-700',
        'warning' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
        default => 'bg-gray-100 border-gray-400 text-gray-700',
    };
@endphp

<div {{ $attributes->merge(['class' => "mb-6 border px-4 py-3 rounded-lg text-sm $classes"]) }}>
    {{ $slot }}
</div>
