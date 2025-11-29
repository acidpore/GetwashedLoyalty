@props(['href', 'variant' => 'primary'])

@php
    $classes = match($variant) {
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 text-white',
        'secondary' => 'bg-white hover:bg-blue-50 text-slate-600 border border-slate-200',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
        default => 'bg-gray-600 hover:bg-gray-700 text-white',
    };
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => "flex items-center justify-center gap-2 w-full font-bold py-4 rounded-xl text-center shadow-sm transition $classes"]) }}>
    {{ $slot }}
</a>
