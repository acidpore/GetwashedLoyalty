@php($r = \App\Services\BusinessAnalytics::report($this->filters))

<x-filament-panels::page class="fi-dashboard-page ga-terminal">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap">

    <div class="ga-toolbar">
        <div class="ga-toolbar-head">
            <span class="ga-live"><i></i>LIVE</span>
            <span>Diperbarui {{ $r['generated_at'] }} WIB</span>
        </div>
        <div class="ga-toolbar-body">
            <div class="ga-toolbar-filters">{{ $this->filtersForm }}</div>
            <dl class="ga-meta">
                <div><dt>Periode</dt><dd>{{ $r['range'] }}</dd></div>
                <div><dt>Pembanding</dt><dd>{{ $r['prev_range'] }}</dd></div>
                <div><dt>Total data</dt><dd>{{ number_format($r['total_visits'], 0, ',', '.') }} kunjungan</dd></div>
            </dl>
        </div>
    </div>

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="['filters' => $this->filters, ...$this->getWidgetData()]"
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
