@php
    $s = $r['segments'];
    $f = $r['frequency'];
    $statusColor = ['info' => 'var(--primary-500)', 'success' => 'var(--success-500)', 'warning' => 'var(--warning-500)', 'danger' => 'var(--danger-500)', 'gray' => 'var(--gray-400)'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Kondisi customer saat ini" description="Semua customer yang pernah datang, dikelompokkan menurut kapan terakhir datang.">
        @if ($s['total'] === 0)
            <p class="ga-empty">Belum ada data customer.</p>
        @else
            <div class="ga-split">
                <div class="ga-bars">
                    @foreach ($s['items'] as $item)
                        @php($pct = $item['count'] / $s['total'] * 100)
                        <div>
                            <div class="ga-bar-head">
                                <span>
                                    <x-filament::badge :color="$item['color']" size="sm" style="display:inline-flex">{{ $item['label'] }}</x-filament::badge>
                                    <span class="ga-hint" style="margin-left:.375rem">{{ $item['hint'] }}</span>
                                </span>
                                <span class="ga-num"><span class="ga-strong">{{ number_format($item['count'], 0, ',', '.') }}</span> <span class="ga-muted">· {{ number_format($pct, 0) }}%</span></span>
                            </div>
                            <div class="ga-bar-track"><div class="ga-bar-fill" style="width:{{ $pct }}%;background:rgb({{ $statusColor[$item['color']] }})"></div></div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <p class="ga-subhead">Berapa kali customer datang (sepanjang waktu)</p>
                    <div class="ga-bars">
                        @foreach ($f['buckets'] as $label => $count)
                            @php($pct = $f['total'] ? $count / $f['total'] * 100 : 0)
                            <div>
                                <div class="ga-bar-head">
                                    <span>{{ $label }}</span>
                                    <span class="ga-num"><span class="ga-strong">{{ number_format($count, 0, ',', '.') }}</span> <span class="ga-muted">· {{ number_format($pct, 0) }}%</span></span>
                                </div>
                                <div class="ga-bar-track"><div class="ga-bar-fill" style="width:{{ $pct }}%;background:#2563eb"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <p class="ga-note">Customer "Mulai jarang" dan "Berisiko hilang" bisa dihubungi lewat menu Broadcast sebelum mereka berhenti datang.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
