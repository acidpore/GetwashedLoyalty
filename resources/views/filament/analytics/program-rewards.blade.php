@php
    use App\Services\BusinessAnalytics as A;
    $m = $r['mix'];
    $w = $r['rewards'];
    $totalProgram = max(1, array_sum($m['programs']));
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Program & hadiah" description="Porsi tiap program di periode ini dan pergerakan hadiah.">
        <div class="ga-split">
            <div class="ga-bars">
                @foreach (A::PROGRAMS as $p => $label)
                    @continue($r['program'] && $r['program'] !== $p)
                    @php($pct = $m['programs'][$p] / $totalProgram * 100)
                    <div>
                        <div class="ga-bar-head">
                            <span><i class="ga-dot" style="background:{{ A::COLORS[$p] }}"></i>{{ $label }}</span>
                            <span class="ga-num"><span class="ga-strong">{{ number_format($m['programs'][$p], 0, ',', '.') }}</span> <span class="ga-muted">kunjungan · {{ number_format($pct, 0) }}%</span></span>
                        </div>
                        <div class="ga-bar-track"><div class="ga-bar-fill" style="width:{{ $pct }}%;background:{{ A::COLORS[$p] }}"></div></div>
                    </div>
                @endforeach
            </div>

            <div class="ga-stat-row">
                @unless ($r['program'])
                    <div class="ga-stat" title="Kunjungan cuci mobil/motor yang sekalian membeli di Coffee Shop">
                        <b>{{ $m['cross_sell'] === null ? '–' : number_format($m['cross_sell'], 1, ',', '.') . '%' }}</b>
                        <span>Cuci + kopi</span>
                    </div>
                @endunless
                <div class="ga-stat">
                    <b>{{ number_format(array_sum($w['claimed']), 0, ',', '.') }}</b>
                    <span>Hadiah diklaim</span>
                </div>
                <div class="ga-stat" title="Customer yang poinnya sudah mencapai hadiah tapi belum ditukar">
                    <b>{{ number_format(array_sum($w['pending']), 0, ',', '.') }}</b>
                    <span>Belum klaim</span>
                </div>
            </div>

            <div>
                <p class="ga-subhead">Hadiah paling sering ditukar</p>
                @if (empty($w['top']))
                    <p class="ga-hint">Belum ada klaim di periode ini.</p>
                @else
                    <table class="ga-table">
                        <tbody>
                            @foreach ($w['top'] as $reward => $count)
                                <tr>
                                    <td>{{ $reward }}</td>
                                    <td class="ga-right ga-num ga-strong">{{ $count }}x</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
