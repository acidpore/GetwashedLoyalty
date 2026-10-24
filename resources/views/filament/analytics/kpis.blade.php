<x-filament-widgets::widget>
    <div class="ga-kpis">
        @foreach ($tiles as $t)
            <div class="ga-kpi ga-{{ $t['tone'] }}" title="{{ $t['hint'] }}">
                <div class="ga-kpi-label">{{ $t['label'] }}</div>
                <div class="ga-kpi-value">{{ $t['value'] }}</div>
                <div class="ga-kpi-delta">
                    @if ($t['delta'])
                        <span class="ga-chip">{{ $t['tone'] === 'up' ? '▲' : ($t['tone'] === 'down' ? '▼' : '■') }} {{ $t['delta'] }}</span>
                        <span class="ga-kpi-prev">sblm {{ $t['previous'] }}</span>
                    @else
                        <span class="ga-kpi-prev">tanpa pembanding</span>
                    @endif
                </div>
                @if ($t['spark'])
                    <svg class="ga-spark" viewBox="0 0 100 28" preserveAspectRatio="none" aria-hidden="true">
                        <polygon points="0,28 {{ $t['spark'] }} 100,28" class="ga-spark-area" />
                        <polyline points="{{ $t['spark'] }}" class="ga-spark-line" vector-effect="non-scaling-stroke" />
                    </svg>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
