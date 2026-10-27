@php
    $rp = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');
    $s = $r['summary'];
    $p = $r['previous'];
    $delta = function ($now, $before) {
        $c = \App\Services\LatteReport::change($now, $before);
        return $c === null ? null : ['up' => $c >= 0, 'text' => ($c >= 0 ? '▲ ' : '▼ ') . number_format(abs($c), 1, ',', '.') . '%'];
    };
    $dailyMax = max(1, $r['daily']->max('revenue'));
    $hourMax = max(1, $r['hours']->max('orders') ?? 1);
@endphp
<x-filament-panels::page class="lt-page">
    <div class="lt-hero">
        <span class="lt-hero-icon"><x-filament::icon icon="heroicon-o-document-chart-bar" /></span>
        <div>
            <h1>Laporan Bisnis</h1>
            <p>{{ $r['from']->locale('id')->translatedFormat('d M Y') }} – {{ $r['to']->locale('id')->translatedFormat('d M Y') }} · dibanding {{ $r['days'] }} hari sebelumnya</p>
        </div>
        <div class="rp-export">
            <button type="button" class="lt-btn" wire:click="downloadPdf" wire:loading.attr="disabled"><x-filament::icon icon="heroicon-m-document-arrow-down" /> PDF</button>
            <button type="button" class="lt-btn rp-btn-alt" wire:click="downloadXlsx" wire:loading.attr="disabled"><x-filament::icon icon="heroicon-m-table-cells" /> Excel</button>
        </div>
    </div>

    <div class="lt-card rp-filter">
        <div class="pos-chips">
            @foreach (['today' => 'Hari ini', '7d' => '7 hari', '30d' => '30 hari', 'month' => 'Bulan ini', 'last_month' => 'Bulan lalu'] as $key => $label)
                <button type="button" wire:click="preset('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
        <label>Dari <input type="date" class="pos-input" wire:model.live="from"></label>
        <label>Sampai <input type="date" class="pos-input" wire:model.live="to"></label>
        <span wire:loading class="rp-loading">Memuat…</span>
    </div>

    <div class="lt-cards">
        @foreach ([
            ['Omzet Bersih', $rp($s['net']), $delta($s['net'], $p['net']), 'green', 'heroicon-o-banknotes'],
            ['Transaksi', number_format($s['orders'], 0, ',', '.'), $delta($s['orders'], $p['orders']), 'blue', 'heroicon-o-receipt-percent'],
            ['Rata-rata / Transaksi', $rp($s['avg']), $delta($s['avg'], $p['avg']), 'orange', 'heroicon-o-shopping-bag'],
            ['Item Terjual', number_format($s['items'], 0, ',', '.'), null, 'pink', 'heroicon-o-cube'],
        ] as [$label, $value, $d, $tone, $icon])
            <div class="lt-card lt-stat lt-{{ $tone }}">
                <span class="lt-stat-icon"><x-filament::icon :icon="$icon" /></span>
                <div>
                    <p class="lt-stat-label">{{ $label }}</p>
                    <p class="lt-stat-value">{{ $value }}</p>
                    <p class="lt-stat-hint">
                        @if ($d)<span @class(['rp-up' => $d['up'], 'rp-down' => ! $d['up']])>{{ $d['text'] }}</span> vs periode lalu
                        @elseif ($label === 'Item Terjual') Tidak termasuk hadiah gratis
                        @else Belum ada data pembanding @endif
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="lt-card lt-strip">
        @foreach ([
            ['Omzet Kotor', $rp($s['gross']), 'heroicon-o-calculator'],
            ['Total Diskon', $rp($s['discount']), 'heroicon-o-tag'],
            ['Dibatalkan', $s['voids'] . ' · ' . $rp($s['void_value']), 'heroicon-o-x-circle'],
            ['Transaksi Member', number_format($r['loyalty']['member_share'], 0) . '%', 'heroicon-o-users'],
            ['Hadiah Diklaim', $r['loyalty']['rewards'], 'heroicon-o-gift'],
        ] as [$label, $value, $icon])
            <div class="lt-mini"><x-filament::icon :icon="$icon" /><div><strong>{{ $value }}</strong><span>{{ $label }}</span></div></div>
        @endforeach
    </div>

    <section class="lt-card lt-panel">
        @php
            $levels = ['critical' => 'Kritis', 'warning' => 'Perhatian', 'good' => 'Positif', 'info' => 'Info'];
            $counts = collect($r['insights'])->countBy('level');
        @endphp
        <header>
            <div><h2>Briefing Manajer</h2><p>Temuan dan tindakan, urut dari yang paling mendesak</p></div>
            <div class="rp-tally">
                @foreach ($levels as $key => $label)
                    @if ($counts[$key] ?? 0)<span class="rp-tag rp-{{ $key }}">{{ $counts[$key] }} {{ $label }}</span>@endif
                @endforeach
            </div>
        </header>
        <ol class="rp-brief">
            @foreach ($r['insights'] as $i => $x)
                <li class="rp-item rp-{{ $x['level'] }}">
                    <span class="rp-no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="rp-body">
                        <div class="rp-head"><span class="rp-tag rp-{{ $x['level'] }}">{{ $levels[$x['level']] }}</span><strong>{{ $x['title'] }}</strong></div>
                        <p>{{ $x['text'] }}</p>
                        <p class="rp-action"><b>Tindakan</b> {{ $x['action'] }}</p>
                    </div>
                    <span class="rp-metric">{{ $x['metric'] }}</span>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="lt-card lt-panel">
        <header><div><h2>Omzet Harian</h2><p>Omzet bersih per hari</p></div></header>
        <div class="rp-daily" role="img" aria-label="Grafik omzet harian">
            @foreach ($r['daily'] as $day)
                <div class="lt-bar" title="{{ $day['label'] }}: {{ $rp($day['revenue']) }} · {{ $day['orders'] }} transaksi">
                    <i style="height: {{ max(1, round($day['revenue'] / $dailyMax * 100)) }}%"></i>
                    @if ($r['days'] <= 14)<span class="lt-bar-label">{{ $day['date']->format('d') }}</span>@endif
                </div>
            @endforeach
        </div>
    </section>

    <div class="lt-grid">
        <section class="lt-card lt-panel">
            <header><div><h2>Peringkat Menu</h2><p>Berdasarkan pendapatan</p></div></header>
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead><tr><th>#</th><th>Menu</th><th class="rp-num">Terjual</th><th class="rp-num">Pendapatan</th><th class="rp-num">Kontribusi</th></tr></thead>
                    <tbody>
                        @forelse ($r['products'] as $i => $row)
                            <tr><td>{{ $i + 1 }}</td><td><strong>{{ $row['name'] }}</strong><small>{{ $row['category'] }}</small></td><td class="rp-num">{{ $row['qty'] }}</td><td class="rp-num">{{ $rp($row['revenue']) }}</td><td class="rp-num">{{ number_format($row['share'], 1, ',', '.') }}%</td></tr>
                        @empty
                            <tr><td colspan="5" class="lt-empty">Belum ada penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="lt-card lt-panel">
            <header><div><h2>Jam Sibuk</h2><p>Jumlah transaksi per jam</p></div></header>
            @forelse ($r['hours'] as $h)
                <div class="rp-hbar">
                    <span>{{ sprintf('%02d.00', $h['hour']) }}</span>
                    <i><b style="width: {{ round($h['orders'] / $hourMax * 100) }}%"></b></i>
                    <span class="rp-num">{{ $h['orders'] }}</span>
                </div>
            @empty
                <p class="lt-empty">Belum ada data.</p>
            @endforelse
        </section>
    </div>

    <div class="lt-grid rp-grid-even">
        <section class="lt-card lt-panel">
            <header><div><h2>Metode Bayar</h2></div></header>
            <table class="rp-table">
                <tbody>
                    @foreach ($r['methods'] as $m)
                        <tr><td>{{ $m['name'] }}</td><td class="rp-num">{{ $m['orders'] }} trx</td><td class="rp-num">{{ $rp($m['revenue']) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <section class="lt-card lt-panel">
            <header><div><h2>Kinerja Kasir</h2></div></header>
            <table class="rp-table">
                <thead><tr><th>Kasir</th><th class="rp-num">Trx</th><th class="rp-num">Omzet</th><th class="rp-num">Batal</th></tr></thead>
                <tbody>
                    @forelse ($r['cashiers'] as $c)
                        <tr><td>{{ $c['name'] }}</td><td class="rp-num">{{ $c['orders'] }}</td><td class="rp-num">{{ $rp($c['revenue']) }}</td><td class="rp-num">{{ $c['voids'] }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="lt-empty">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>

    <div class="lt-grid rp-grid-even">
        <section class="lt-card lt-panel">
            <header><div><h2>Selisih Kas per Shift</h2></div></header>
            <table class="rp-table">
                <thead><tr><th>Shift</th><th class="rp-num">Seharusnya</th><th class="rp-num">Selisih</th></tr></thead>
                <tbody>
                    @forelse ($r['shifts'] as $sh)
                        <tr><td>{{ $sh['cashier'] }}<small>{{ $sh['opened']->format('d/m H:i') }}–{{ $sh['closed']->format('H:i') }}</small></td><td class="rp-num">{{ $rp($sh['expected']) }}</td>
                            <td @class(['rp-num', 'rp-down' => $sh['difference'], 'rp-up' => ! $sh['difference']])>{{ $sh['difference'] > 0 ? '+' : '' }}{{ $rp($sh['difference']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="lt-empty">Belum ada shift yang ditutup.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section class="lt-card lt-panel">
            <header><div><h2>Stok Menipis</h2><p>Kondisi saat ini</p></div></header>
            <table class="rp-table">
                <tbody>
                    @forelse ($r['low_stock'] as $ing)
                        <tr><td>{{ $ing->name }}</td><td class="rp-num rp-down">{{ rtrim(rtrim(number_format($ing->stock, 2, ',', '.'), '0'), ',') }} {{ $ing->unit }}</td><td class="rp-num">min {{ rtrim(rtrim(number_format($ing->min_stock, 2, ',', '.'), '0'), ',') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="lt-empty">Semua stok aman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>
