@php($r = \App\Services\BusinessAnalytics::report($this->filters))

<x-filament-panels::page class="fi-dashboard-page ga-terminal">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap">

    <div class="ga-statusbar">
        <span class="ga-live"><i></i>LIVE</span>
        <span><b>PERIODE</b> {{ $r['range'] }}</span>
        <span><b>PEMBANDING</b> {{ $r['prev_range'] }}</span>
        <span><b>PROGRAM</b> {{ $r['program'] ? \App\Services\BusinessAnalytics::PROGRAMS[$r['program']] : 'Semua' }}</span>
        <span class="ga-statusbar-end"><b>DATA</b> {{ number_format($r['total_visits'], 0, ',', '.') }} kunjungan · diperbarui {{ $r['generated_at'] }} WIB</span>
    </div>

    {{ $this->filtersForm }}

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="['filters' => $this->filters, ...$this->getWidgetData()]"
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
