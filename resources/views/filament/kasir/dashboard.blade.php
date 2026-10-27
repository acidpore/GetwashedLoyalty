<x-filament-panels::page class="lt-page">
    <div class="lt-hero">
        <span class="lt-hero-icon"><x-filament::icon icon="heroicon-o-squares-2x2" /></span>
        <div>
            <h1>Dashboard</h1>
            <p>Ringkasan penjualan Getwashed Latte · {{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ \App\Filament\Kasir\Pages\Pos::getUrl() }}" class="lt-btn">
            <x-filament::icon icon="heroicon-m-shopping-cart" /> Buka Kasir
        </a>
    </div>

    <div class="lt-cards">
        @foreach ($cards as $card)
            <div class="lt-card lt-stat lt-{{ $card['tone'] }}">
                <span class="lt-stat-icon"><x-filament::icon :icon="$card['icon']" /></span>
                <div>
                    <p class="lt-stat-label">{{ $card['label'] }}</p>
                    <p class="lt-stat-value">{{ $card['value'] }}</p>
                    <p class="lt-stat-hint">{{ $card['hint'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="lt-card lt-strip">
        @foreach ($strip as $item)
            <div class="lt-mini">
                <x-filament::icon :icon="$item['icon']" />
                <div>
                    <strong>{{ $item['value'] }}</strong>
                    <span>{{ $item['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="lt-grid">
        <section class="lt-card lt-panel">
            <header><h2>Penjualan 7 Hari Terakhir</h2><p>Pendapatan harian</p></header>
            <div class="lt-bars" role="img" aria-label="Grafik pendapatan 7 hari terakhir">
                @foreach ($week as $day)
                    <div class="lt-bar" title="{{ $day['date'] }}: Rp{{ number_format($day['revenue'], 0, ',', '.') }}">
                        <span class="lt-bar-value">{{ $day['revenue'] ? number_format($day['revenue'] / 1000, 0, ',', '.') . 'rb' : '' }}</span>
                        <i style="height: {{ max(2, round($day['revenue'] / $weekMax * 100)) }}%" @class(['is-today' => $loop->last])></i>
                        <span class="lt-bar-label">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="lt-card lt-panel">
            <header><h2>Menu Terlaris</h2><p>Bulan ini</p></header>
            @forelse ($top as $i => $item)
                <div class="lt-rank">
                    <span class="lt-rank-no">{{ $i + 1 }}</span>
                    <div><strong>{{ $item->name }}</strong><span>Rp{{ number_format($item->revenue, 0, ',', '.') }}</span></div>
                    <b>{{ $item->qty }}×</b>
                </div>
            @empty
                <p class="lt-empty">Belum ada penjualan bulan ini.</p>
            @endforelse
        </section>
    </div>

    <section class="lt-card lt-panel">
        <header>
            <div><h2>Transaksi Terbaru</h2><p>6 transaksi terakhir</p></div>
            <a href="{{ \App\Filament\Resources\Latte\OrderResource::getUrl() }}" class="lt-link">Lihat semua</a>
        </header>
        @forelse ($recent as $order)
            <div class="lt-row">
                <span class="lt-row-icon"><x-filament::icon icon="heroicon-o-receipt-percent" /></span>
                <div><strong>{{ $order->number }}</strong><span>{{ $order->customer?->user?->name ?? 'Umum' }} · {{ $order->created_at->format('H:i') }}</span></div>
                <span @class(['lt-pill', 'lt-pill-cash' => ! $order->voided_at && $order->payment_method === 'cash'])>{{ $order->voided_at ? 'Dibatalkan' : $methods[$order->payment_method] }}</span>
                <b>Rp{{ number_format($order->total, 0, ',', '.') }}</b>
            </div>
        @empty
            <p class="lt-empty">Belum ada transaksi. Mulai dari menu Kasir.</p>
        @endforelse
    </section>
</x-filament-panels::page>
