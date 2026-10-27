<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RewardClaim;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sales report for Getwashed Latte over a date range, compared with the
 * equally long period right before it. Feeds the Laporan page, the PDF and
 * the XLSX so all three always show the same numbers.
 */
class LatteReport
{
    public const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public static function build(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $length = (int) $from->diffInDays($to) + 1;
        $prevFrom = $from->subDays($length);
        $prevTo = $from->subSecond();

        $orders = Order::with(['items', 'cashier:id,name'])->whereBetween('created_at', [$from, $to])->get();
        $paid = $orders->whereNull('voided_at');
        $voided = $orders->whereNotNull('voided_at');
        $prev = Order::paid()->whereBetween('created_at', [$prevFrom, $prevTo])->get(['id', 'total', 'discount', 'customer_id']);

        $summary = self::summary($paid, $voided);
        $previous = self::summary($prev, collect());

        $items = $paid->flatMap->items->where('price', '>', 0);
        $categories = Product::pluck('category', 'id');

        $products = $items->groupBy('name')->map(fn (Collection $rows, string $name) => [
            'name' => $name,
            'category' => Product::CATEGORIES[$categories[$rows->first()->product_id] ?? ''] ?? '-',
            'qty' => $rows->sum('qty'),
            'revenue' => $rows->sum(fn ($i) => $i->price * $i->qty),
        ])->sortByDesc('revenue')->values();
        $itemRevenue = max(1, $products->sum('revenue'));
        $running = 0;
        $products = $products->map(function ($row) use ($itemRevenue, &$running) {
            $running += $row['revenue'];

            return $row + ['share' => $row['revenue'] / $itemRevenue * 100, 'cumulative' => $running / $itemRevenue * 100];
        });

        $byCategory = $products->groupBy('category')->map(fn ($rows, $name) => [
            'name' => $name, 'qty' => $rows->sum('qty'), 'revenue' => $rows->sum('revenue'), 'share' => $rows->sum('revenue') / $itemRevenue * 100,
        ])->sortByDesc('revenue')->values();

        $daily = collect();
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            $rows = $paid->filter(fn ($o) => $o->created_at->isSameDay($day));
            $daily->push([
                'date' => $day,
                'label' => $day->locale('id')->translatedFormat('D, d M'),
                'orders' => $rows->count(),
                'revenue' => $rows->sum('total'),
                'discount' => $rows->sum('discount'),
                'avg' => $rows->count() ? intdiv($rows->sum('total'), $rows->count()) : 0,
            ]);
        }

        $hours = collect(range(0, 23))->mapWithKeys(fn ($h) => [$h => ['hour' => $h, 'orders' => 0, 'revenue' => 0]]);
        $weekdays = collect(self::DAYS)->map(fn ($d) => ['day' => $d, 'orders' => 0, 'revenue' => 0, 'dates' => []])->all();
        foreach ($paid as $o) {
            $h = $hours[$o->created_at->hour];
            $hours[$o->created_at->hour] = ['hour' => $h['hour'], 'orders' => $h['orders'] + 1, 'revenue' => $h['revenue'] + $o->total];
            $w = $o->created_at->dayOfWeekIso - 1;
            $weekdays[$w]['orders']++;
            $weekdays[$w]['revenue'] += $o->total;
        }
        // Average per occurrence of that weekday in the range, so ranges that hold more Mondays don't skew it.
        $weekdays = collect($weekdays)->map(function ($row, $i) use ($from, $to) {
            $count = collect(range(0, (int) $from->diffInDays($to)))->filter(fn ($d) => $from->addDays($d)->dayOfWeekIso - 1 === $i)->count();

            return ['day' => $row['day'], 'orders' => $row['orders'], 'revenue' => $row['revenue'], 'avg_revenue' => $count ? intdiv($row['revenue'], $count) : 0];
        });
        $activeHours = $hours->where('orders', '>', 0)->values();

        $methods = collect(Order::METHODS)->map(fn ($label, $key) => [
            'name' => $label,
            'orders' => $paid->where('payment_method', $key)->count(),
            'revenue' => $paid->where('payment_method', $key)->sum('total'),
        ])->values();

        $cashiers = $paid->groupBy(fn ($o) => $o->cashier?->name ?? '-')->map(fn ($rows, $name) => [
            'name' => $name,
            'orders' => $rows->count(),
            'revenue' => $rows->sum('total'),
            'avg' => intdiv($rows->sum('total'), max(1, $rows->count())),
            'voids' => $voided->filter(fn ($o) => ($o->cashier?->name ?? '-') === $name)->count(),
        ])->sortByDesc('revenue')->values();

        $promos = $paid->where('discount', '>', 0)->groupBy('promo_name')->map(fn ($rows, $name) => [
            'name' => $name ?: 'Diskon', 'orders' => $rows->count(), 'discount' => $rows->sum('discount'), 'revenue' => $rows->sum('total'),
        ])->sortByDesc('discount')->values();

        $shifts = CashShift::with('user:id,name')->whereBetween('opened_at', [$from, $to])->whereNotNull('closed_at')->orderBy('opened_at')->get()
            ->map(fn (CashShift $s) => [
                'cashier' => $s->user?->name ?? '-',
                'opened' => $s->opened_at, 'closed' => $s->closed_at,
                'opening' => $s->opening_cash, 'expected' => (int) $s->expected_cash, 'counted' => (int) $s->closing_cash,
                'difference' => $s->difference(), 'note' => $s->note,
            ]);

        $members = $paid->whereNotNull('customer_id');
        $loyalty = [
            'member_orders' => $members->count(),
            'member_share' => $paid->count() ? $members->count() / $paid->count() * 100 : 0,
            'member_avg' => $members->count() ? intdiv($members->sum('total'), $members->count()) : 0,
            'guest_avg' => ($g = $paid->whereNull('customer_id'))->count() ? intdiv($g->sum('total'), $g->count()) : 0,
            'unique_members' => $members->pluck('customer_id')->unique()->count(),
            'repeat_members' => $members->countBy('customer_id')->filter(fn ($n) => $n > 1)->count(),
            'points' => $paid->sum('points_earned'),
            'rewards' => RewardClaim::where('loyalty_type', 'coffeeshop')->whereBetween('claimed_at', [$from, $to])->count(),
        ];

        $report = [
            'from' => $from, 'to' => $to, 'days' => $length,
            'prev_from' => $prevFrom, 'prev_to' => $prevTo,
            'generated_at' => now(),
            'summary' => $summary,
            'previous' => $previous,
            'daily' => $daily,
            'hours' => $activeHours,
            'weekdays' => $weekdays,
            'methods' => $methods,
            'products' => $products,
            'categories' => $byCategory,
            'cashiers' => $cashiers,
            'promos' => $promos,
            'shifts' => $shifts,
            'loyalty' => $loyalty,
            'voids' => $voided->map(fn ($o) => ['number' => $o->number, 'at' => $o->created_at, 'total' => $o->total, 'reason' => $o->void_reason, 'cashier' => $o->cashier?->name])->values(),
            'low_stock' => Ingredient::low()->orderBy('name')->get(),
            'unsold' => Product::where('is_active', true)->whereNotIn('name', $products->pluck('name'))->orderBy('name')->pluck('name'),
        ];

        return $report + ['insights' => self::insights($report)];
    }

    private static function summary(Collection $paid, Collection $voided): array
    {
        $count = $paid->count();
        $net = (int) $paid->sum('total');
        $discount = (int) $paid->sum('discount');

        return [
            'gross' => $net + $discount,
            'discount' => $discount,
            'net' => $net,
            'orders' => $count,
            'avg' => $count ? intdiv($net, $count) : 0,
            'items' => $paid->sum(fn ($o) => $o->relationLoaded('items') ? $o->items->where('price', '>', 0)->sum('qty') : 0),
            'voids' => $voided->count(),
            'void_value' => (int) $voided->sum('total'),
        ];
    }

    public static function change(int|float $now, int|float $before): ?float
    {
        return $before ? ($now - $before) / $before * 100 : null;
    }

    /** Plain-language findings, most important first. Each: [tone, title, text]. */
    private static function insights(array $r): array
    {
        $s = $r['summary'];
        $p = $r['previous'];
        $rp = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');
        $pct = fn ($n) => number_format($n, 1, ',', '.') . '%';
        $out = [];

        if (! $s['orders']) {
            return [['flat', 'Belum ada penjualan', 'Tidak ada transaksi pada periode ini, jadi belum ada yang bisa dianalisis.']];
        }

        if (($c = self::change($s['net'], $p['net'])) !== null) {
            $out[] = [$c >= 0 ? 'up' : 'down', 'Omzet ' . ($c >= 0 ? 'naik' : 'turun') . ' ' . $pct(abs($c)),
                "Omzet bersih {$rp($s['net'])} dibanding {$rp($p['net'])} pada {$r['days']} hari sebelumnya."
                . (($o = self::change($s['orders'], $p['orders'])) !== null && ($a = self::change($s['avg'], $p['avg'])) !== null
                    ? ' Penyebabnya: jumlah transaksi ' . ($o >= 0 ? 'naik ' : 'turun ') . $pct(abs($o)) . ', nilai rata-rata per transaksi ' . ($a >= 0 ? 'naik ' : 'turun ') . $pct(abs($a)) . '.'
                    : '')];
        }

        if ($r['products']->count() >= 3) {
            $top = $r['products']->first();
            $core = $r['products']->takeWhile(fn ($row) => $row['cumulative'] <= 80)->count() + 1;
            $out[] = ['flat', 'Menu andalan: ' . $top['name'],
                "{$top['name']} menyumbang {$pct($top['share'])} pendapatan menu ({$top['qty']} terjual). "
                . "{$core} dari {$r['products']->count()} menu menghasilkan 80% pendapatan — pastikan menu-menu ini tidak pernah kehabisan bahan."];
        }

        $busiestDay = $r['weekdays']->sortByDesc('avg_revenue')->first();
        $quietDay = $r['weekdays']->filter(fn ($d) => $d['orders'] > 0)->sortBy('avg_revenue')->first();
        if ($r['days'] >= 7 && $busiestDay && $quietDay && $busiestDay['day'] !== $quietDay['day']) {
            $out[] = ['flat', "Hari tersibuk {$busiestDay['day']}, tersepi {$quietDay['day']}",
                "Rata-rata omzet hari {$busiestDay['day']} {$rp($busiestDay['avg_revenue'])}, sedangkan {$quietDay['day']} hanya {$rp($quietDay['avg_revenue'])}. "
                . "Promo khusus hari {$quietDay['day']} bisa membantu meratakan penjualan."];
        }

        if ($r['hours']->isNotEmpty()) {
            $peak = $r['hours']->sortByDesc('orders')->first();
            $window = sprintf('%02d.00–%02d.00', $peak['hour'], $peak['hour'] + 1);
            $out[] = ['flat', "Jam sibuk {$window}",
                "{$peak['orders']} transaksi (" . $pct($peak['orders'] / $s['orders'] * 100) . ") terjadi di jam ini. Siapkan barista dan bahan ekstra sebelum jam tersebut."];
        }

        $l = $r['loyalty'];
        if ($l['member_orders'] && $l['guest_avg']) {
            $diff = self::change($l['member_avg'], $l['guest_avg']);
            $out[] = [$diff >= 0 ? 'up' : 'flat', $pct($l['member_share']) . ' transaksi dari member',
                "Rata-rata belanja member {$rp($l['member_avg'])} vs non-member {$rp($l['guest_avg'])} (" . ($diff >= 0 ? 'lebih tinggi ' : 'lebih rendah ') . $pct(abs($diff)) . "). "
                . "{$l['repeat_members']} dari {$l['unique_members']} member datang lebih dari sekali."
                . ($l['member_share'] < 40 ? ' Ajak lebih banyak pembeli untuk daftar member di kasir.' : '')];
        } elseif (! $l['member_orders']) {
            $out[] = ['down', 'Belum ada transaksi member', 'Tidak ada pembeli yang tercatat sebagai member. Tawarkan daftar member di kasir supaya pembeli kembali lagi.'];
        }

        if ($s['discount']) {
            $out[] = ['flat', 'Diskon ' . $rp($s['discount']),
                'Diskon memotong ' . $pct($s['discount'] / max(1, $s['gross']) * 100) . ' dari omzet kotor.'
                . ($r['promos']->isNotEmpty() ? " Promo paling sering dipakai: {$r['promos']->sortByDesc('orders')->first()['name']}." : '')];
        }

        if ($s['voids']) {
            $rate = $s['voids'] / ($s['orders'] + $s['voids']) * 100;
            $out[] = [$rate > 5 ? 'down' : 'flat', "{$s['voids']} transaksi dibatalkan",
                "Senilai {$rp($s['void_value'])} ({$pct($rate)} dari semua transaksi)." . ($rate > 5 ? ' Angka ini cukup tinggi — cek alasan pembatalan per kasir.' : '')];
        }

        if (($bad = $r['shifts']->filter(fn ($sh) => $sh['difference']))->isNotEmpty()) {
            $out[] = ['down', "Selisih kas di {$bad->count()} shift",
                'Total selisih ' . $rp($bad->sum('difference')) . '. Cek ulang shift yang selisihnya besar di tabel Shift.'];
        }

        if ($r['low_stock']->isNotEmpty()) {
            $out[] = ['down', $r['low_stock']->count() . ' bahan hampir habis', 'Segera belanja: ' . $r['low_stock']->pluck('name')->implode(', ') . '.'];
        }

        if ($r['unsold']->isNotEmpty()) {
            $out[] = ['flat', $r['unsold']->count() . ' menu tidak laku', 'Tidak terjual sama sekali: ' . $r['unsold']->take(6)->implode(', ') . ($r['unsold']->count() > 6 ? ', dll' : '') . '. Pertimbangkan promo atau ganti menu.'];
        }

        return $out;
    }
}
