@php
    $c = $r['cohorts'];
    $max = max(1, collect($c['rows'])->pluck('cells')->flatten()->filter()->max() ?? 1);
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Retensi customer baru" description="Dari customer yang pertama datang di suatu bulan, berapa persen yang datang lagi di bulan-bulan berikutnya.">
        @if (empty($c['rows']))
            <p class="ga-empty">Butuh minimal satu bulan data penuh.</p>
        @else
            <div class="ga-scroll">
                <table class="ga-table ga-cohort">
                    <thead>
                        <tr>
                            <th>Bulan pertama</th>
                            <th class="ga-right">Customer</th>
                            @for ($k = 1; $k <= 5; $k++)
                                <th style="text-align:center">+{{ $k }} bln</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($c['rows'] as $row)
                            <tr>
                                <td class="ga-strong">{{ $row['label'] }}</td>
                                <td class="ga-right ga-num">{{ number_format($row['size'], 0, ',', '.') }}</td>
                                @foreach ($row['cells'] as $k => $pct)
                                    @if ($pct === null)
                                        <td class="ga-cell ga-muted" title="Bulan ini belum selesai">·</td>
                                    @else
                                        <td class="ga-cell" title="{{ $pct }}% dari {{ $row['size'] }} customer {{ $row['label'] }} datang lagi {{ $k + 1 }} bulan kemudian"
                                            style="background:rgba(37,99,235,{{ sprintf('%.2f', 0.08 + 0.92 * $pct / $max) }});color:{{ $pct / $max > .55 ? '#fff' : 'inherit' }}">{{ $pct }}%</td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="ga-note">Angka yang terus mengecil itu normal. Yang perlu dijaga: kolom "+1 bln" tidak turun dari bulan ke bulan.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
