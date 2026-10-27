@php
    $h = $r['heatmap'];
    $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    // One hue, light to dark: more visits, darker cell.
    $shade = fn ($n) => $n ? sprintf('rgba(37,99,235,%.2f)', 0.12 + 0.88 * $n / max(1, $h['max'])) : null;
@endphp

<x-filament-widgets::widget>
    <x-filament::section heading="Jam ramai" description="Kapan customer datang, per hari dan jam. Pakai untuk mengatur jadwal staf dan waktu promo.">
        @if ($h['max'] === 0)
            <p class="ga-empty">Belum ada data kunjungan di periode ini.</p>
        @else
            <div class="ga-scroll">
                <table class="ga-heat" aria-label="Jumlah kunjungan per hari dan jam">
                    <thead>
                        <tr>
                            <th></th>
                            @for ($hour = $h['from_hour']; $hour <= $h['to_hour']; $hour++)
                                <th scope="col">{{ sprintf('%02d', $hour) }}</th>
                            @endfor
                            <th scope="col" style="padding-left:.75rem">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Services\BusinessAnalytics::DAYS as $d => $short)
                            <tr>
                                <th scope="row">{{ $short }}</th>
                                @for ($hour = $h['from_hour']; $hour <= $h['to_hour']; $hour++)
                                    @php($n = $h['grid'][$d][$hour] ?? 0)
                                    <td title="{{ $days[$d] }} {{ sprintf('%02d.00', $hour) }}: {{ $n }} kunjungan"
                                        style="{{ $n ? 'background:' . $shade($n) . ';' : '' }}--fg:{{ $n / max(1, $h['max']) > .5 ? '#fff' : 'rgb(var(--gray-950))' }}">{{ $n ?: '' }}</td>
                                @endfor
                                <th scope="row" class="ga-num" style="padding-left:.75rem;text-align:center">{{ number_format($h['by_day'][$d], 0, ',', '.') }}</th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="ga-legend">
                Sepi
                @foreach ([0.15, 0.35, 0.55, 0.75, 1] as $a)
                    <i style="background:rgba(37,99,235,{{ $a }})"></i>
                @endforeach
                Ramai
                <span style="margin-left:auto">Arahkan kursor ke kotak untuk angka persisnya.</span>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
