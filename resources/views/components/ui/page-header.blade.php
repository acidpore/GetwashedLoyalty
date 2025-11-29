@props(['icon' => null, 'title', 'description'])

<div {{ $attributes->merge(['class' => 'text-center']) }}>
    @if($icon)
        <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-400 to-cyan-400 rounded-2xl flex items-center justify-center shadow-lg">
            {{ $icon }}
        </div>
    @endif
    <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ $title }}</h1>
    <p class="text-gray-600">{{ $description }}</p>
</div>
