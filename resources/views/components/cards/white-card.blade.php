@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-3xl shadow-2xl p-8']) }}>
    @if($title || $description)
        <div class="text-center mb-8">
            @if($title)
                <h2 class="text-2xl font-bold text-slate-700">{{ $title }}</h2>
            @endif
            @if($description)
                <p class="text-slate-400 text-sm mt-2">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
