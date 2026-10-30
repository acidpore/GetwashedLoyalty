@php
    $rp = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');
    $pc = fn ($n) => number_format($n, 1, ',', '.') . '%';
    $s = $r['summary'];
    $p = $r['previous'];
    $delta = function ($now, $before) use ($pc) {
        $c = \App\Services\LatteReport::change($now, $before);
        return $c === null ? '-' : ($c >= 0 ? '+' : '-') . $pc(abs($c));
    };
    $dailyMax = max(1, $r['daily']->max('revenue'));
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Getwashed Latte</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1c1917; }
    h1 { font-size: 18px; margin: 0; }
    h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #fbbf24; }
    .muted { color: #78716c; }
    .head { border-bottom: 3px solid #f59e0b; padding-bottom: 8px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: center; font-size: 9px; color: #78716c; border-bottom: 1px solid #d6d3d1; padding: 4px; }
    td { text-align: center; }
    td { padding: 4px; border-bottom: 1px solid #f5f5f4; vertical-align: top; }
    .num { text-align: center; white-space: nowrap; }
    .kpi td { border: 1px solid #e7e5e4; padding: 8px; width: 25%; }
    .kpi b { display: block; font-size: 14px; margin: 2px 0; }
    .up { color: #15803d; } .down { color: #b91c1c; }
    .brief td { padding: 7px 6px; border-bottom: 1px solid #e7e5e4; }
    .brief .no { width: 18px; font-weight: bold; color: #a8a29e; }
    .brief tr.critical td.no { border-left: 3px solid #dc2626; } .brief tr.warning td.no { border-left: 3px solid #d97706; }
    .brief tr.good td.no { border-left: 3px solid #16a34a; } .brief tr.info td.no { border-left: 3px solid #78716c; }
    .tag { font-size: 7.5px; font-weight: bold; letter-spacing: .5px; padding: 1px 4px; color: #fff; }
    .tag.critical { background: #dc2626; } .tag.warning { background: #d97706; } .tag.good { background: #16a34a; } .tag.info { background: #57534e; }
    .act { font-weight: bold; color: #1c1917; }
    .metric { width: 90px; font-size: 13px; font-weight: bold; }
    .bar { background: #fbbf24; height: 8px; }
    .half { width: 49%; vertical-align: top; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>
    <div class="head">
        <h1>Laporan Bisnis Getwashed Latte</h1>
        <div class="muted">
            Periode {{ $r['from']->locale('id')->translatedFormat('d F Y') }} – {{ $r['to']->locale('id')->translatedFormat('d F Y') }} ({{ $r['days'] }} hari)
            · dibanding {{ $r['prev_from']->format('d/m/Y') }} – {{ $r['prev_to']->format('d/m/Y') }}
            · dibuat {{ $r['generated_at']->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="kpi">
        <tr>
            <td>Omzet bersih<b>{{ $rp($s['net']) }}</b><span class="muted">{{ $delta($s['net'], $p['net']) }} vs periode lalu</span></td>
            <td>Transaksi<b>{{ number_format($s['orders'], 0, ',', '.') }}</b><span class="muted">{{ $delta($s['orders'], $p['orders']) }}</span></td>
            <td>Rata-rata / transaksi<b>{{ $rp($s['avg']) }}</b><span class="muted">{{ $delta($s['avg'], $p['avg']) }}</span></td>
            <td>Item terjual<b>{{ number_format($s['items'], 0, ',', '.') }}</b><span class="muted">tanpa hadiah gratis</span></td>
        </tr>
        <tr>
            <td>Omzet kotor<b>{{ $rp($s['gross']) }}</b></td>
            <td>Diskon<b>{{ $rp($s['discount']) }}</b></td>
            <td>Dibatalkan<b>{{ $s['voids'] }} trx</b><span class="muted">{{ $rp($s['void_value']) }}</span></td>
            <td>Transaksi member<b>{{ $pc($r['loyalty']['member_share']) }}</b><span class="muted">{{ $r['loyalty']['rewards'] }} hadiah diklaim</span></td>
        </tr>
    </table>

    <h2>Briefing Manajer</h2>
    @php $levels = ['critical' => 'KRITIS', 'warning' => 'PERHATIAN', 'good' => 'POSITIF', 'info' => 'INFO']; @endphp
    <table class="brief">
        @foreach ($r['insights'] as $i => $x)
            <tr class="{{ $x['level'] }}">
                <td class="no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                <td><span class="tag {{ $x['level'] }}">{{ $levels[$x['level']] }}</span> <b>{{ $x['title'] }}</b><br>{{ $x['text'] }}<br><span class="act">&#9656; Tindakan: {{ $x['action'] }}</span></td>
                <td class="num metric">{{ $x['metric'] }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Omzet Harian</h2>
    <table>
        <thead><tr><th>Tanggal</th><th class="num">Trx</th><th class="num">Omzet</th><th class="num">Diskon</th><th style="width:40%"></th></tr></thead>
        <tbody>
            @foreach ($r['daily'] as $d)
                <tr><td>{{ $d['label'] }}</td><td class="num">{{ $d['orders'] }}</td><td class="num">{{ $rp($d['revenue']) }}</td><td class="num">{{ $d['discount'] ? $rp($d['discount']) : '-' }}</td>
                    <td><div class="bar" style="width: {{ max(0.5, round($d['revenue'] / $dailyMax * 100, 1)) }}%"></div></td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Peringkat Menu</h2>
    <table>
        <thead><tr><th>#</th><th>Menu</th><th>Kategori</th><th class="num">Terjual</th><th class="num">Pendapatan</th><th class="num">Kontribusi</th><th class="num">Kumulatif</th></tr></thead>
        <tbody>
            @forelse ($r['products'] as $i => $x)
                <tr><td>{{ $i + 1 }}</td><td>{{ $x['name'] }}</td><td>{{ $x['category'] }}</td><td class="num">{{ $x['qty'] }}</td><td class="num">{{ $rp($x['revenue']) }}</td><td class="num">{{ $pc($x['share']) }}</td><td class="num">{{ $pc($x['cumulative']) }}</td></tr>
            @empty
                <tr><td colspan="7" class="muted">Belum ada penjualan.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($r['unsold']->isNotEmpty())
        <p class="muted">Tidak terjual: {{ $r['unsold']->implode(', ') }}</p>
    @endif

    <table style="margin-top: 6px"><tr>
        <td class="half">
            <h2>Kategori</h2>
            <table>
                @foreach ($r['categories'] as $c)
                    <tr><td>{{ $c['name'] }}</td><td class="num">{{ $c['qty'] }}</td><td class="num">{{ $rp($c['revenue']) }}</td><td class="num">{{ $pc($c['share']) }}</td></tr>
                @endforeach
            </table>
            <h2>Metode Bayar</h2>
            <table>
                @foreach ($r['methods'] as $m)
                    <tr><td>{{ $m['name'] }}</td><td class="num">{{ $m['orders'] }} trx</td><td class="num">{{ $rp($m['revenue']) }}</td></tr>
                @endforeach
            </table>
        </td>
        <td style="width: 2%"></td>
        <td class="half">
            <h2>Hari dalam Seminggu</h2>
            <table>
                <tr><th>Hari</th><th class="num">Trx</th><th class="num">Rata-rata omzet</th></tr>
                @foreach ($r['weekdays'] as $w)
                    <tr><td>{{ $w['day'] }}</td><td class="num">{{ $w['orders'] }}</td><td class="num">{{ $rp($w['avg_revenue']) }}</td></tr>
                @endforeach
            </table>
            <h2>Jam Sibuk</h2>
            <table>
                @forelse ($r['hours']->sortByDesc('orders')->take(6) as $h)
                    <tr><td>{{ sprintf('%02d.00–%02d.00', $h['hour'], $h['hour'] + 1) }}</td><td class="num">{{ $h['orders'] }} trx</td><td class="num">{{ $rp($h['revenue']) }}</td></tr>
                @empty
                    <tr><td class="muted">Belum ada data.</td></tr>
                @endforelse
            </table>
        </td>
    </tr></table>

    <h2>Member &amp; Loyalty</h2>
    <table>
        <tr><td>Transaksi member</td><td class="num">{{ $r['loyalty']['member_orders'] }} ({{ $pc($r['loyalty']['member_share']) }})</td>
            <td>Rata-rata belanja member</td><td class="num">{{ $rp($r['loyalty']['member_avg']) }}</td></tr>
        <tr><td>Member unik</td><td class="num">{{ $r['loyalty']['unique_members'] }}</td>
            <td>Rata-rata belanja non-member</td><td class="num">{{ $rp($r['loyalty']['guest_avg']) }}</td></tr>
        <tr><td>Member kembali (&gt;1x)</td><td class="num">{{ $r['loyalty']['repeat_members'] }}</td>
            <td>Poin diberikan / hadiah diklaim</td><td class="num">{{ $r['loyalty']['points'] }} / {{ $r['loyalty']['rewards'] }}</td></tr>
    </table>

    <h2>Kinerja Kasir</h2>
    <table>
        <thead><tr><th>Kasir</th><th class="num">Trx</th><th class="num">Omzet</th><th class="num">Rata-rata</th><th class="num">Dibatalkan</th></tr></thead>
        @forelse ($r['cashiers'] as $c)
            <tr><td>{{ $c['name'] }}</td><td class="num">{{ $c['orders'] }}</td><td class="num">{{ $rp($c['revenue']) }}</td><td class="num">{{ $rp($c['avg']) }}</td><td class="num">{{ $c['voids'] }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">Belum ada data.</td></tr>
        @endforelse
    </table>

    @if ($r['promos']->isNotEmpty())
        <h2>Promo &amp; Diskon</h2>
        <table>
            <thead><tr><th>Promo</th><th class="num">Trx</th><th class="num">Total diskon</th><th class="num">Omzet</th></tr></thead>
            @foreach ($r['promos'] as $x)
                <tr><td>{{ $x['name'] }}</td><td class="num">{{ $x['orders'] }}</td><td class="num">{{ $rp($x['discount']) }}</td><td class="num">{{ $rp($x['revenue']) }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($r['shifts']->isNotEmpty())
        <h2>Shift &amp; Kas</h2>
        <table>
            <thead><tr><th>Kasir</th><th>Waktu</th><th class="num">Modal</th><th class="num">Seharusnya</th><th class="num">Dihitung</th><th class="num">Selisih</th></tr></thead>
            @foreach ($r['shifts'] as $x)
                <tr><td>{{ $x['cashier'] }}</td><td>{{ $x['opened']->format('d/m H:i') }}–{{ $x['closed']->format('H:i') }}</td><td class="num">{{ $rp($x['opening']) }}</td><td class="num">{{ $rp($x['expected']) }}</td><td class="num">{{ $rp($x['counted']) }}</td>
                    <td class="num {{ $x['difference'] ? 'down' : 'up' }}">{{ $x['difference'] > 0 ? '+' : ($x['difference'] < 0 ? '-' : '') }}{{ $rp(abs($x['difference'])) }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($r['voids']->isNotEmpty())
        <h2>Transaksi Dibatalkan</h2>
        <table>
            <thead><tr><th>No.</th><th>Waktu</th><th>Kasir</th><th class="num">Nilai</th><th>Alasan</th></tr></thead>
            @foreach ($r['voids'] as $v)
                <tr><td>{{ $v['number'] }}</td><td>{{ $v['at']->format('d/m H:i') }}</td><td>{{ $v['cashier'] ?? '-' }}</td><td class="num">{{ $rp($v['total']) }}</td><td>{{ $v['reason'] }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($r['low_stock']->isNotEmpty())
        <h2>Stok Menipis (saat laporan dibuat)</h2>
        <table>
            @foreach ($r['low_stock'] as $i)
                <tr><td>{{ $i->name }}</td><td class="num down">{{ (float) $i->stock }} {{ $i->unit }}</td><td class="num">min {{ (float) $i->min_stock }}</td></tr>
            @endforeach
        </table>
    @endif
</body>
</html>
