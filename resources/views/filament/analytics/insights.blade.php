<x-filament-widgets::widget>
    <x-filament::section heading="Ringkasan" description="Poin penting dari data {{ \App\Services\BusinessAnalytics::PERIODS[$r['days']] }}{{ $r['program'] ? ', program ' . \App\Services\BusinessAnalytics::PROGRAMS[$r['program']] : '' }}.">
        @if (empty($r['insights']))
            <p class="ga-empty">Belum ada data kunjungan di periode ini.</p>
        @else
            <div class="ga-insights">
                @foreach ($r['insights'] as $insight)
                    <div class="ga-insight ga-tone-{{ $insight['tone'] }}">
                        <x-filament::icon :icon="$insight['icon']" />
                        <span>{{ $insight['text'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
