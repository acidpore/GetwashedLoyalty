<?php

namespace App\Filament\Kasir\Pages;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RewardClaim;
use Filament\Pages\Dashboard;
use Illuminate\Support\Carbon;

class LatteDashboard extends Dashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Utama';

    protected static string $view = 'filament.kasir.dashboard';

    public function getHeading(): string
    {
        return '';
    }

    protected function getViewData(): array
    {
        $today = Order::paid()->whereDate('created_at', today());
        $month = Order::paid()->where('created_at', '>=', now()->startOfMonth());
        $todayCount = (clone $today)->count();
        $todayRevenue = (int) (clone $today)->sum('total');
        $yesterdayRevenue = (int) Order::paid()->whereDate('created_at', today()->subDay())->sum('total');

        $daily = Order::paid()->where('created_at', '>=', today()->subDays(6))
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('day')->pluck('revenue', 'day');
        $week = collect(range(6, 0))->map(function (int $ago) use ($daily) {
            $day = today()->subDays($ago);

            return ['label' => $day->locale('id')->translatedFormat('D'), 'date' => $day->translatedFormat('d M'), 'revenue' => (int) ($daily[$day->toDateString()] ?? 0)];
        });

        return [
            'cards' => [
                ['label' => 'Pendapatan Hari Ini', 'value' => $this->rp($todayRevenue), 'hint' => $this->versus($todayRevenue, $yesterdayRevenue), 'icon' => 'heroicon-o-arrow-trending-up', 'tone' => 'green'],
                ['label' => 'Transaksi Hari Ini', 'value' => number_format($todayCount, 0, ',', '.'), 'hint' => (clone $today)->whereNotNull('customer_id')->count() . ' dari member loyalty', 'icon' => 'heroicon-o-receipt-percent', 'tone' => 'blue'],
                ['label' => 'Rata-rata / Transaksi', 'value' => $this->rp($todayCount ? intdiv($todayRevenue, $todayCount) : 0), 'hint' => 'Nilai keranjang hari ini', 'icon' => 'heroicon-o-shopping-bag', 'tone' => 'orange'],
                ['label' => 'Hadiah Diklaim', 'value' => number_format(RewardClaim::where('loyalty_type', 'coffeeshop')->whereDate('claimed_at', today())->count(), 0, ',', '.'), 'hint' => 'Hari ini', 'icon' => 'heroicon-o-gift', 'tone' => 'pink'],
            ],
            'strip' => [
                ['label' => 'Pendapatan Bulan Ini', 'value' => $this->rp((int) (clone $month)->sum('total')), 'icon' => 'heroicon-o-calendar'],
                ['label' => 'Transaksi Bulan Ini', 'value' => number_format((clone $month)->count(), 0, ',', '.'), 'icon' => 'heroicon-o-document-text'],
                ['label' => 'Item Terjual Bulan Ini', 'value' => number_format((int) OrderItem::whereIn('order_id', (clone $month)->select('id'))->where('price', '>', 0)->sum('qty'), 0, ',', '.'), 'icon' => 'heroicon-o-cube'],
                ['label' => 'Bahan Hampir Habis', 'value' => \App\Models\Ingredient::low()->count(), 'icon' => 'heroicon-o-exclamation-triangle'],
                ['label' => 'Member Latte', 'value' => number_format(Customer::where('coffeeshop_total_visits', '>', 0)->count(), 0, ',', '.'), 'icon' => 'heroicon-o-users'],
            ],
            'week' => $week,
            'weekMax' => max(1, $week->max('revenue')),
            'top' => OrderItem::whereIn('order_id', (clone $month)->select('id'))->where('price', '>', 0)
                ->selectRaw('name, SUM(qty) as qty, SUM(qty * price) as revenue')
                ->groupBy('name')->orderByDesc('qty')->limit(5)->get(),
            'recent' => Order::with('customer.user')->latest()->limit(6)->get(),
            'methods' => Order::METHODS,
        ];
    }

    private function rp(int $amount): string
    {
        return 'Rp' . number_format($amount, 0, ',', '.');
    }

    private function versus(int $now, int $before): string
    {
        if (! $before) {
            return 'Kemarin belum ada penjualan';
        }

        $change = ($now - $before) / $before * 100;

        return ($change >= 0 ? '▲ ' : '▼ ') . number_format(abs($change), 0) . '% dari kemarin';
    }
}
