<x-filament-widgets::widget>
    <x-filament::section heading="Customer paling setia" description="10 customer dengan kunjungan terbanyak di periode ini. Cocok untuk diberi apresiasi khusus.">
        @if (empty($r['top']))
            <p class="ga-empty">Belum ada data kunjungan di periode ini.</p>
        @else
            <div class="ga-scroll">
                <table class="ga-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th class="ga-right">Periode ini</th>
                            <th class="ga-right">Sepanjang waktu</th>
                            <th class="ga-right">Terakhir datang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($r['top'] as $i => $c)
                            <tr>
                                <td class="ga-muted ga-num">{{ $i + 1 }}</td>
                                <td>
                                    <span class="ga-strong">{{ $c['name'] }}</span>
                                    <span class="ga-hint" style="display:block">{{ $c['phone'] }}</span>
                                </td>
                                <td class="ga-right ga-num ga-strong">{{ $c['visits'] }}x</td>
                                <td class="ga-right ga-num">{{ $c['lifetime'] }}x</td>
                                <td class="ga-right" style="white-space:nowrap">{{ $c['last'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
