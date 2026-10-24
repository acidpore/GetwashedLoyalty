@props(['type' => 'info'])

@php
    [$tone, $icon] = match ($type) {
        'success' => ['bg-positive-soft text-positive', 'check_circle'],
        'error' => ['bg-danger-soft text-danger', 'error'],
        'warning' => ['bg-caution-soft text-caution', 'warning'],
        default => ['bg-subtle text-ink-2', 'info'],
    };
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class("flex items-start gap-3 rounded-2xl px-4 py-3 text-sm font-medium $tone") }}>
    <span class="material-symbols-outlined shrink-0 mt-px">{{ $icon }}</span>
    <div class="min-w-0">{{ $slot }}</div>
</div>
